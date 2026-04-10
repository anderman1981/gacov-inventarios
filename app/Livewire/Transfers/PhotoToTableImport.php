<?php

declare(strict_types=1);

namespace App\Livewire\Transfers;

use App\Models\Product;
use App\Models\TransferPhotoImport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

final class PhotoToTableImport extends Component
{
    use WithFileUploads;

    private const GEMINI_PROMPT = <<<'PROMPT'
Analiza esta fotografía de una planilla física de surtido/traslado de inventario.

Objetivo:
- Extraer una tabla confiable en JSON.
- La tabla que nos interesa para traslados contiene únicamente una columna COD o código del producto, una columna de nombre del producto y una columna SB.
- Algunos números pueden estar impresos, manuscritos o con tachones leves.

Instrucciones obligatorias:
1. Devuelve únicamente JSON válido, sin markdown, sin comentarios, sin texto adicional.
2. Conserva cada fila de producto real detectada.
3. Si un valor numérico es dudoso pero legible, devuelve el entero más probable.
4. Si una celda está vacía, devuelve 0.
5. Si una fila no tiene código claro, omítela.
6. Normaliza los códigos de producto como texto sin espacios extra.
7. Normaliza el nombre del producto sin caracteres basura de OCR.
8. Ignora cualquier columna adicional distinta de COD, producto y SB.
9. Todos los valores de SB deben ser enteros mayores o iguales a cero.
10. No inventes productos ni columnas que no existan en la imagen.

Formato de salida obligatorio:
{
  "filas": [
    {
      "cod": "732",
      "producto": "ROSCAS BUNGA",
      "sb": 48
    }
  ]
}
PROMPT;

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    /** @var array<int, array{cod:string,producto:string,sb:int,maquinas:array<string,int>}> */
    public array $parsedRows = [];

    /** @var array<int, string> */
    public array $machineColumns = [];

    /** @var array<string, array{id:int,name:string,code:string}> */
    public array $productCatalog = [];

    public ?int $editingRowIndex = null;

    public ?string $selectedQuantityColumn = null;

    public bool $processing = false;

    public ?string $processingMessage = null;

    public ?string $lastError = null;

    public ?string $summaryMessage = null;

    public ?int $activeImportLogId = null;

    public ?string $lastProviderUsed = null;

    public function mount(): void
    {
        $this->authorizeUsage();

        $catalog = [];

        Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->each(function (Product $product) use (&$catalog): void {
                $productData = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => strtoupper(trim((string) $product->code)),
                ];

                foreach ($this->catalogLookupKeys((string) $product->code) as $lookupKey) {
                    $catalog[$lookupKey] ??= $productData;
                }
            });

        $this->productCatalog = $catalog;
    }

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:4'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:8192'],
            'parsedRows' => ['array'],
            'parsedRows.*.cod' => ['required', 'string', 'max:50'],
            'parsedRows.*.producto' => ['required', 'string', 'max:255'],
            'parsedRows.*.sb' => ['required', 'integer', 'min:0'],
            'parsedRows.*.maquinas' => ['array'],
            'parsedRows.*.maquinas.*' => ['integer', 'min:0'],
            'selectedQuantityColumn' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'photos.required' => 'Debes seleccionar al menos una foto.',
            'photos.min' => 'Debes subir al menos una foto.',
            'photos.max' => 'Solo puedes procesar hasta 4 fotos por intento.',
            'photos.*.image' => 'Cada archivo debe ser una imagen válida.',
            'photos.*.mimes' => 'Solo se permiten imágenes JPG o PNG.',
            'photos.*.max' => 'Cada imagen no puede superar los 8 MB.',
            'parsedRows.*.cod.required' => 'Cada fila debe tener un código.',
            'parsedRows.*.producto.required' => 'Cada fila debe tener un producto.',
            'parsedRows.*.sb.required' => 'La columna SB es obligatoria.',
            'parsedRows.*.sb.integer' => 'La cantidad SB debe ser un entero.',
            'parsedRows.*.sb.min' => 'La cantidad SB no puede ser negativa.',
            'parsedRows.*.maquinas.*.integer' => 'Las cantidades por máquina deben ser enteras.',
            'parsedRows.*.maquinas.*.min' => 'Las cantidades por máquina no pueden ser negativas.',
        ];
    }

    public function updated(string $property): void
    {
        if (Str::startsWith($property, 'parsedRows.')) {
            $this->validateOnly($property);
        }
    }

    public function uploadAndProcessPhoto(): void
    {
        $this->authorizeUsage();
        $this->extendExecutionWindow();
        $this->validate([
            'photos' => ['required', 'array', 'min:1', 'max:4'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:8192'],
        ]);

        $this->resetProcessingState();
        $this->processing = true;
        $this->dispatchProgress(12, 'Preparando imágenes para análisis...');

        $log = TransferPhotoImport::create([
            'user_id' => (int) auth()->id(),
            'status' => 'procesando',
            'source_files' => collect($this->photos)
                ->map(fn (TemporaryUploadedFile $photo): array => [
                    'name' => $photo->getClientOriginalName(),
                    'size' => $photo->getSize(),
                    'mime_type' => $photo->getMimeType(),
                ])->values()->all(),
        ]);

        $this->activeImportLogId = $log->id;

        try {
            $mergedRows = [];
            $providersUsed = [];
            $fallbackUsed = false;

            foreach ($this->photos as $index => $photo) {
                $this->extendExecutionWindow();
                $this->dispatchProgress(
                    20 + ($index * 20),
                    "Analizando foto " . ($index + 1) . " de " . count($this->photos) . " con IA..."
                );

                $result = $this->processWithAvailableProviders($photo, $index + 1, count($this->photos));
                $payload = $result['payload'];
                $providersUsed[$result['provider']] = $this->providerDisplayName($result['provider']);
                $fallbackUsed = $fallbackUsed || $result['fallback_used'];
                $rows = Arr::get($payload, 'filas', []);

                if (! is_array($rows) || $rows === []) {
                    throw ValidationException::withMessages([
                        'photos' => 'No se detectaron filas válidas en la planilla. Revisa enfoque, luz o recorte.',
                    ]);
                }

                foreach ($rows as $row) {
                    $normalizedRow = $this->normalizeRow($row);
                    $codeKey = strtoupper($normalizedRow['cod']);

                    if (array_key_exists($codeKey, $mergedRows)) {
                        $mergedRows[$codeKey] = $this->mergeRows($mergedRows[$codeKey], $normalizedRow);
                        continue;
                    }

                    $mergedRows[$codeKey] = $normalizedRow;
                }

            }

            $this->parsedRows = array_values($mergedRows);
            usort($this->parsedRows, fn (array $left, array $right): int => strcmp($left['producto'], $right['producto']));

            $this->machineColumns = [];
            $this->selectedQuantityColumn = 'sb';

            $this->validatePreviewTable();

            $matchedCodes = collect($this->parsedRows)
                ->filter(fn (array $row): bool => $this->resolveCatalogProduct((string) $row['cod']) !== null)
                ->count();

            $log->update([
                'status' => 'completado',
                'detected_rows' => count($this->parsedRows),
                'detected_machine_columns' => $this->machineColumns,
                'payload' => [
                    'filas' => $this->parsedRows,
                    'providers_used' => array_values($providersUsed),
                    'fallback_used' => $fallbackUsed,
                ],
            ]);

            $this->applyRowsToTransfer('sb', true);
            $this->lastProviderUsed = $this->buildProviderSummary(array_values($providersUsed), $fallbackUsed);

            $this->summaryMessage = "La IA detectó " . count($this->parsedRows) . " fila(s) con {$this->lastProviderUsed}. "
                . "{$matchedCodes} código(s) coinciden con el catálogo activo. La columna SB se aplicó automáticamente al traslado.";

            $this->dispatchProgress(100, 'Planilla procesada correctamente.');
            $this->dispatchToast('success', "La planilla fue transcrita con {$this->lastProviderUsed} y la columna SB ya quedó cargada en el traslado.");
        } catch (Throwable $exception) {
            $this->lastError = $exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->first()
                : $exception->getMessage();

            $log->update([
                'status' => 'error',
                'error_message' => $this->lastError,
            ]);

            Log::error('transfer_photo_import_failed', [
                'user_id' => auth()->id(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatchToast('error', $this->lastError ?? 'No fue posible procesar la planilla.');
        } finally {
            $this->processing = false;
            $this->processingMessage = null;
        }
    }

    public function startEditingRow(int $index): void
    {
        $this->editingRowIndex = $index;
    }

    public function stopEditingRow(): void
    {
        $this->validatePreviewTable();
        $this->editingRowIndex = null;
    }

    public function removeRow(int $index): void
    {
        unset($this->parsedRows[$index]);
        $this->parsedRows = array_values($this->parsedRows);

        if ($this->editingRowIndex === $index) {
            $this->editingRowIndex = null;
        }

        $this->summaryMessage = "Fila eliminada de la previsualización.";
        $this->dispatchToast('success', 'La fila fue eliminada de la tabla.');
    }

    public function applyToTransfer(): void
    {
        $this->authorizeUsage();
        $this->validatePreviewTable();

        if ($this->parsedRows === []) {
            throw ValidationException::withMessages([
                'parsedRows' => 'Primero debes procesar una planilla con IA.',
            ]);
        }

        $this->applyRowsToTransfer($this->selectedQuantityColumn ?: 'sb');
    }

    public function footerTotals(): array
    {
        $totals = ['sb' => 0];

        foreach ($this->machineColumns as $column) {
            $totals[$column] = 0;
        }

        foreach ($this->parsedRows as $row) {
            $totals['sb'] += (int) ($row['sb'] ?? 0);

            foreach ($this->machineColumns as $column) {
                $totals[$column] += (int) ($row['maquinas'][$column] ?? 0);
            }
        }

        return $totals;
    }

    public function availableQuantityColumns(): array
    {
        return array_merge(['sb'], $this->machineColumns);
    }

    public function previewUrls(): array
    {
        return collect($this->photos)
            ->map(function (TemporaryUploadedFile $photo): array {
                return [
                    'name' => $photo->getClientOriginalName(),
                    'url' => $photo->temporaryUrl(),
                ];
            })
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.transfers.photo-to-table-import', [
            'footerTotals' => $this->footerTotals(),
            'previewUrls' => $this->previewUrls(),
            'availableQuantityColumns' => $this->availableQuantityColumns(),
        ]);
    }

    private function authorizeUsage(): void
    {
        $user = auth()->user();

        abort_unless(
            $user !== null
            && $user->can('transfers.create')
            && ($user->hasRole('super_admin') || $user->can('inventory.load_excel')),
            403
        );
    }

    private function processWithGemini(UploadedFile $photo): array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $endpoint = rtrim((string) config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $timeout = max(15, (int) config('services.gemini.timeout', 90));
        $connectTimeout = max(5, (int) config('services.gemini.connect_timeout', 15));

        if ($apiKey === '') {
            throw new RuntimeException('Falta configurar GEMINI_API_KEY en el entorno.');
        }

        $mimeType = $photo->getMimeType() ?: 'image/jpeg';
        $binary = file_get_contents($photo->getRealPath());

        if ($binary === false) {
            throw new RuntimeException('No fue posible leer una de las imágenes seleccionadas.');
        }

        $models = $this->geminiModels();
        $lastException = null;

        foreach ($models as $index => $model) {
            $response = Http::acceptJson()
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->retry(2, 800)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$endpoint}/models/{$model}:generateContent", [
                    'contents' => [[
                        'parts' => [
                            ['text' => self::GEMINI_PROMPT],
                            ['inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => base64_encode($binary),
                            ]],
                        ],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                        'responseJsonSchema' => $this->geminiResponseSchema(),
                    ],
                ]);

            if (! $response->successful()) {
                $message = $this->extractProviderMessage($response, 'Gemini devolvió un error al intentar procesar la planilla.');
                $friendlyMessage = $this->formatProviderError('gemini', $message, $response->status());

                if ($this->shouldRetryGeminiWithNextModel($response->status(), $message, $index, count($models))) {
                    $lastException = new RuntimeException($friendlyMessage);
                    continue;
                }

                throw new RuntimeException($friendlyMessage);
            }

            $jsonText = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! is_string($jsonText) || trim($jsonText) === '') {
                throw new RuntimeException('Gemini no devolvió contenido estructurado para la imagen.');
            }

            $decoded = json_decode($this->extractJsonPayload($jsonText), true);

            if (! is_array($decoded) || ! isset($decoded['filas']) || ! is_array($decoded['filas'])) {
                throw new RuntimeException('La respuesta de Gemini no contiene el JSON esperado.');
            }

            return $decoded;
        }

        throw $lastException ?? new RuntimeException('Gemini no respondió con un modelo disponible para procesar la planilla.');
    }

    /**
     * @return array{payload:array,provider:string,fallback_used:bool}
     */
    private function processWithAvailableProviders(UploadedFile $photo, int $photoNumber, int $photoCount): array
    {
        $attemptErrors = [];
        $attemptedProviders = 0;

        foreach (['gemini', 'openai'] as $provider) {
            if (! $this->isProviderConfigured($provider)) {
                continue;
            }

            $attemptedProviders++;

            try {
                $payload = match ($provider) {
                    'gemini' => $this->processWithGemini($photo),
                    'openai' => $this->processWithOpenAi($photo),
                    default => throw new RuntimeException('Proveedor de IA no soportado.'),
                };

                return [
                    'payload' => $payload,
                    'provider' => $provider,
                    'fallback_used' => count($attemptErrors) > 0,
                ];
            } catch (Throwable $exception) {
                $attemptErrors[] = $this->providerDisplayName($provider) . ': ' . $this->friendlyExceptionMessage($provider, $exception);

                Log::warning('transfer_photo_import_provider_failed', [
                    'user_id' => auth()->id(),
                    'provider' => $provider,
                    'photo_number' => $photoNumber,
                    'photo_count' => $photoCount,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                if ($provider === 'gemini' && $this->isProviderConfigured('openai')) {
                    $this->dispatchProgress(
                        min(95, 25 + ($photoNumber * 20)),
                        "Gemini no respondió para la foto {$photoNumber}. Intentando con OpenAI..."
                    );
                }
            }
        }

        if ($attemptedProviders === 0) {
            throw new RuntimeException('Debes configurar GEMINI_API_KEY u OPENAI_API_KEY para procesar la planilla.');
        }

        throw new RuntimeException($this->buildUnavailableProvidersMessage($attemptErrors));
    }

    private function processWithOpenAi(UploadedFile $photo): array
    {
        $apiKey = (string) config('services.openai.api_key');
        $model = (string) config('services.openai.model', 'gpt-4o-mini');
        $endpoint = (string) config('services.openai.endpoint', 'https://api.openai.com/v1/responses');
        $timeout = max(15, (int) config('services.openai.timeout', 90));
        $connectTimeout = max(5, (int) config('services.openai.connect_timeout', 15));

        if ($apiKey === '') {
            throw new RuntimeException('Falta configurar OPENAI_API_KEY en el entorno.');
        }

        $mimeType = $photo->getMimeType() ?: 'image/jpeg';
        $binary = file_get_contents($photo->getRealPath());

        if ($binary === false) {
            throw new RuntimeException('No fue posible leer una de las imágenes seleccionadas.');
        }

        $imageDataUrl = 'data:' . $mimeType . ';base64,' . base64_encode($binary);

        $response = Http::acceptJson()
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->retry(1, 700)
            ->withToken($apiKey)
            ->post($endpoint, [
                'model' => $model,
                'store' => false,
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => self::GEMINI_PROMPT,
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => $imageDataUrl,
                            'detail' => 'high',
                        ],
                    ],
                ]],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'transfer_photo_table',
                        'strict' => false,
                        'schema' => $this->geminiResponseSchema(),
                    ],
                ],
            ]);

        $this->guardOpenAiResponse($response);

        $jsonText = $this->extractOpenAiOutputText($response);
        $decoded = json_decode($this->extractJsonPayload($jsonText), true);

        if (! is_array($decoded) || ! isset($decoded['filas']) || ! is_array($decoded['filas'])) {
            throw new RuntimeException('La respuesta de OpenAI no contiene el JSON esperado.');
        }

        return $decoded;
    }

    private function guardGeminiResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $this->extractProviderMessage($response, 'Gemini devolvió un error al intentar procesar la planilla.');

        throw new RuntimeException($this->formatProviderError('gemini', $message, $response->status()));
    }

    private function guardOpenAiResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $this->extractProviderMessage($response, 'OpenAI devolvió un error al intentar procesar la planilla.');

        throw new RuntimeException($this->formatProviderError('openai', $message, $response->status()));
    }

    private function extractOpenAiOutputText(Response $response): string
    {
        $directOutput = data_get($response->json(), 'output_text');

        if (is_string($directOutput) && trim($directOutput) !== '') {
            return $directOutput;
        }

        foreach ((array) data_get($response->json(), 'output', []) as $outputItem) {
            foreach ((array) data_get($outputItem, 'content', []) as $contentItem) {
                if (($contentItem['type'] ?? null) === 'output_text' && is_string($contentItem['text'] ?? null)) {
                    return (string) $contentItem['text'];
                }

                if (($contentItem['type'] ?? null) === 'refusal' && is_string($contentItem['refusal'] ?? null)) {
                    throw new RuntimeException((string) $contentItem['refusal']);
                }
            }
        }

        throw new RuntimeException('OpenAI no devolvió contenido estructurado para la imagen.');
    }

    private function normalizeRow(array $row): array
    {
        return [
            'cod' => strtoupper(trim((string) ($row['cod'] ?? ''))),
            'producto' => trim((string) ($row['producto'] ?? '')),
            'sb' => max(0, (int) ($row['sb'] ?? 0)),
            'maquinas' => [],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function catalogLookupKeys(string $code): array
    {
        $variants = [];
        $raw = strtoupper(trim($code));

        if ($raw !== '') {
            $variants[] = $raw;
        }

        $normalized = $this->normalizeProductCode($code);

        if ($normalized !== '' && ! in_array($normalized, $variants, true)) {
            $variants[] = $normalized;
        }

        if ($normalized !== '' && preg_match('/^\d+$/', $normalized) === 1) {
            $withoutLeadingZeroes = ltrim($normalized, '0');
            $withoutLeadingZeroes = $withoutLeadingZeroes === '' ? '0' : $withoutLeadingZeroes;

            if (! in_array($withoutLeadingZeroes, $variants, true)) {
                $variants[] = $withoutLeadingZeroes;
            }
        }

        return $variants;
    }

    private function normalizeProductCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', trim($code)));
    }

    /**
     * @return array{id:int,name:string,code:string}|null
     */
    private function resolveCatalogProduct(string $code): ?array
    {
        foreach ($this->catalogLookupKeys($code) as $lookupKey) {
            if (isset($this->productCatalog[$lookupKey])) {
                return $this->productCatalog[$lookupKey];
            }
        }

        return null;
    }

    private function mergeRows(array $original, array $replacement): array
    {
        return [
            'cod' => $original['cod'],
            'producto' => $replacement['producto'] !== '' ? $replacement['producto'] : $original['producto'],
            'sb' => max($original['sb'], $replacement['sb']),
            'maquinas' => array_merge($original['maquinas'], $replacement['maquinas']),
        ];
    }

    private function validatePreviewTable(): void
    {
        $this->validate([
            'parsedRows' => ['required', 'array', 'min:1'],
            'parsedRows.*.cod' => ['required', 'string', 'max:50'],
            'parsedRows.*.producto' => ['required', 'string', 'max:255'],
            'parsedRows.*.sb' => ['required', 'integer', 'min:0'],
            'parsedRows.*.maquinas' => ['array'],
            'parsedRows.*.maquinas.*' => ['integer', 'min:0'],
        ]);
    }

    private function geminiResponseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'filas' => [
                    'type' => 'array',
                    'description' => 'Listado de filas detectadas en la planilla.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'cod' => [
                                'type' => 'string',
                                'description' => 'Código del producto detectado en la fila.',
                            ],
                            'producto' => [
                                'type' => 'string',
                                'description' => 'Nombre del producto detectado.',
                            ],
                            'sb' => [
                                'type' => 'integer',
                                'description' => 'Cantidad de la columna SB.',
                                'minimum' => 0,
                            ],
                        ],
                        'required' => ['cod', 'producto', 'sb'],
                    ],
                ],
            ],
            'required' => ['filas'],
        ];
    }

    private function extractJsonPayload(string $jsonText): string
    {
        $trimmed = trim($jsonText);

        if (Str::startsWith($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $trimmed) ?? $trimmed;
        }

        return trim($trimmed);
    }

    private function extendExecutionWindow(): void
    {
        $timeLimit = max(
            120,
            (int) config('services.gemini.execution_time_limit', 300),
            (int) config('services.openai.execution_time_limit', 300),
        );

        if (function_exists('set_time_limit')) {
            @set_time_limit($timeLimit);
        }

        @ini_set('max_execution_time', (string) $timeLimit);
    }

    private function dispatchProgress(int $progress, string $message): void
    {
        $this->processingMessage = $message;
        $this->dispatch('transfer-photo-import-progress', progress: $progress, message: $message);
    }

    private function dispatchToast(string $type, string $message): void
    {
        $this->dispatch('transfer-photo-import-toast', type: $type, message: $message);
    }

    private function resetProcessingState(): void
    {
        $this->lastError = null;
        $this->lastProviderUsed = null;
        $this->summaryMessage = null;
        $this->editingRowIndex = null;
        $this->parsedRows = [];
        $this->machineColumns = [];
        $this->selectedQuantityColumn = 'sb';
    }

    private function isProviderConfigured(string $provider): bool
    {
        $apiKey = (string) config("services.{$provider}.api_key");

        return trim($apiKey) !== '';
    }

    /**
     * @return array<int, string>
     */
    private function geminiModels(): array
    {
        $primaryModel = trim((string) config('services.gemini.model', 'gemini-2.5-flash'));
        $fallbackModels = array_map(
            static fn (mixed $model): string => trim((string) $model),
            (array) config('services.gemini.fallback_models', [])
        );

        return array_values(array_unique(array_filter(array_merge([$primaryModel], $fallbackModels))));
    }

    private function providerDisplayName(string $provider): string
    {
        return match ($provider) {
            'gemini' => 'Gemini',
            'openai' => 'OpenAI',
            default => ucfirst($provider),
        };
    }

    private function extractProviderMessage(Response $response, string $fallbackMessage): string
    {
        return (string) (data_get($response->json(), 'error.message')
            ?? data_get($response->json(), 'message')
            ?? $fallbackMessage);
    }

    private function shouldRetryGeminiWithNextModel(?int $status, string $message, int $index, int $modelCount): bool
    {
        if ($index >= ($modelCount - 1)) {
            return false;
        }

        $normalizedMessage = Str::lower($message);

        return in_array($status, [429, 500, 503], true)
            || Str::contains($normalizedMessage, ['high demand', 'temporarily unavailable', 'overloaded', 'try again later']);
    }

    private function formatProviderError(string $provider, string $message, ?int $status = null): string
    {
        $normalizedMessage = Str::lower($message);

        if ($provider === 'gemini') {
            if ($status === 503 || Str::contains($normalizedMessage, ['high demand', 'temporarily unavailable', 'overloaded'])) {
                return 'Gemini está con alta demanda en este momento. Intenta de nuevo en unos minutos.';
            }

            if ($status === 403 || Str::contains($normalizedMessage, ['denied access', 'contact support'])) {
                return 'Gemini rechazó este proyecto por permisos o acceso denegado. Revisa la configuración del proyecto en Google AI Studio o cambia de proveedor.';
            }

            if ($status === 429) {
                if (Str::contains($normalizedMessage, ['quota', 'billing', 'exceeded your current quota'])) {
                    return 'Gemini no tiene cuota disponible en este proyecto. Revisa facturación, límites y credenciales antes de volver a intentar.';
                }

                return 'Gemini rechazó la solicitud por límite temporal de uso. Espera un momento y vuelve a intentar.';
            }
        }

        if ($provider === 'openai') {
            if ($status === 429 || Str::contains($normalizedMessage, ['quota', 'billing', 'insufficient_quota', 'exceeded your current quota'])) {
                return 'OpenAI no tiene cuota disponible en esta cuenta. Revisa el plan y la facturación antes de volver a intentar.';
            }

            if ($status === 401 || Str::contains($normalizedMessage, ['invalid api key', 'incorrect api key'])) {
                return 'La clave de OpenAI no es válida o no está autorizada para este proyecto.';
            }
        }

        return $message;
    }

    private function friendlyExceptionMessage(string $provider, Throwable $exception): string
    {
        return $this->formatProviderError($provider, $exception->getMessage());
    }

    /**
     * @param  array<int, string>  $attemptErrors
     */
    private function buildUnavailableProvidersMessage(array $attemptErrors): string
    {
        return 'No fue posible procesar la planilla en este momento. '
            . implode(' ', array_values(array_unique(array_filter($attemptErrors))))
            . ' Puedes reintentar en unos minutos o revisar la configuración y cuota de los proveedores.';
    }

    /**
     * @param  array<int, string>  $providersUsed
     */
    private function buildProviderSummary(array $providersUsed, bool $fallbackUsed): string
    {
        $providersUsed = array_values(array_unique(array_filter($providersUsed)));

        if ($providersUsed === []) {
            return 'IA';
        }

        if ($fallbackUsed && count($providersUsed) > 1) {
            return implode(' + ', $providersUsed) . ' como respaldo';
        }

        return implode(' + ', $providersUsed);
    }

    private function applyRowsToTransfer(string $quantityColumn, bool $automatic = false): void
    {
        if (! in_array($quantityColumn, $this->availableQuantityColumns(), true)) {
            throw ValidationException::withMessages([
                'selectedQuantityColumn' => 'La columna seleccionada no es válida para aplicar al traslado.',
            ]);
        }

        $appliedRows = [];
        $missingCodes = [];

        foreach ($this->parsedRows as $row) {
            $code = strtoupper(trim((string) $row['cod']));
            $quantity = $quantityColumn === 'sb'
                ? (int) ($row['sb'] ?? 0)
                : (int) ($row['maquinas'][$quantityColumn] ?? 0);

            $catalogProduct = $this->resolveCatalogProduct($code);

            if ($catalogProduct === null) {
                $missingCodes[] = $code;
                continue;
            }

            $appliedRows[] = [
                'code' => $code,
                'catalogCode' => $catalogProduct['code'],
                'productId' => $catalogProduct['id'],
                'quantity' => max(0, $quantity),
                'product' => $row['producto'],
            ];
        }

        if ($appliedRows === []) {
            throw ValidationException::withMessages([
                'selectedQuantityColumn' => 'Ninguna fila válida coincide con el catálogo para aplicar al traslado.',
            ]);
        }

        if ($this->activeImportLogId !== null) {
            TransferPhotoImport::query()
                ->whereKey($this->activeImportLogId)
                ->update([
                    'status' => 'aplicado',
                    'applied_rows' => count($appliedRows),
                    'payload' => [
                        'filas' => $this->parsedRows,
                        'quantity_column' => $quantityColumn,
                        'missing_codes' => $missingCodes,
                        'automatic' => $automatic,
                    ],
                ]);
        }

        $this->dispatch('transfer-photo-import-applied', rows: $appliedRows, missingCodes: $missingCodes);

        $message = count($appliedRows) . " producto(s) aplicado(s) al formulario desde la columna {$quantityColumn}.";
        if ($missingCodes !== []) {
            $message .= ' Códigos no encontrados: ' . implode(', ', array_slice($missingCodes, 0, 6));
        }

        $this->summaryMessage = $automatic
            ? $message . ' Aplicación automática completada.'
            : $message;

        if (! $automatic) {
            $this->dispatchToast('success', $message);
        }
    }
}
