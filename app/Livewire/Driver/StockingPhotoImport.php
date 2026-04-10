<?php

declare(strict_types=1);

namespace App\Livewire\Driver;

use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
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

final class StockingPhotoImport extends Component
{
    use WithFileUploads;

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    /** @var array<int, array{cod:string,producto:string,maquinas:array<string,int>}> */
    public array $parsedRows = [];

    /** @var array<int, string> */
    public array $machineColumns = [];

    /** @var array<string, array{id:int,name:string,code:string}> */
    public array $productCatalog = [];

    /** @var array<string, array{id:int,name:string,code:string}> */
    public array $routeMachines = [];

    public ?string $routeName = null;

    public ?string $routeCode = null;

    public ?string $sheetRouteName = null;

    public ?string $sheetOperatorName = null;

    public ?string $processingMessage = null;

    public ?string $summaryMessage = null;

    public ?string $lastError = null;

    public bool $processing = false;

    public bool $manualMode = false;

    public function mount(?int $routeId = null): void
    {
        $user = auth()->user();
        $route = $routeId !== null
            ? Route::query()->whereKey($routeId)->where('is_active', true)->first()
            : $user?->route;

        $this->routeName = $route?->name;
        $this->routeCode = $route?->code;

        $this->productCatalog = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (Product $product): array => [
                $this->normalizeProductCode((string) $product->code) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => strtoupper(trim((string) $product->code)),
                ],
            ])
            ->all();

        $this->routeMachines = Machine::query()
            ->where('route_id', $route?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (Machine $machine): array => [
                $this->normalizeMachineCode((string) $machine->code) => [
                    'id' => $machine->id,
                    'name' => $machine->name,
                    'code' => strtoupper(trim((string) $machine->code)),
                ],
            ])
            ->all();
    }

    public function uploadAndProcessPhoto(): void
    {
        $this->authorizeUsage();
        $this->extendExecutionWindow();
        $this->validate([
            'photos' => ['required', 'array', 'min:1', 'max:4'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:8192'],
        ]);

        $this->resetState();
        $this->processing = true;
        $this->dispatchProgress(10, 'Preparando planillas de surtido...');

        try {
            $mergedRows = [];
            $detectedMachineColumns = [];
            $providersUsed = [];
            $detectedRoute = null;
            $detectedOperator = null;

            foreach ($this->photos as $index => $photo) {
                $this->extendExecutionWindow();
                $this->dispatchProgress(
                    20 + ($index * 20),
                    'Analizando planilla ' . ($index + 1) . ' de ' . count($this->photos) . ' con IA...'
                );

                $result = $this->processWithAvailableProviders($photo, $index + 1, count($this->photos));
                $providersUsed[$result['provider']] = $this->providerDisplayName($result['provider']);
                $payload = $result['payload'];
                $rows = Arr::get($payload, 'filas', []);

                if (! is_array($rows) || $rows === []) {
                    throw ValidationException::withMessages([
                        'photos' => 'No se detectaron filas válidas en la planilla de surtido.',
                    ]);
                }

                $detectedRoute ??= $this->normalizeNullableString(Arr::get($payload, 'ruta_detectada'));
                $detectedOperator ??= $this->normalizeNullableString(Arr::get($payload, 'rutero_detectado'));

                foreach ($rows as $row) {
                    $normalizedRow = $this->normalizeRow($row);
                    $codeKey = $this->normalizeProductCode($normalizedRow['cod']);

                    if ($codeKey === '') {
                        continue;
                    }

                    if (array_key_exists($codeKey, $mergedRows)) {
                        $mergedRows[$codeKey] = $this->mergeRows($mergedRows[$codeKey], $normalizedRow);
                    } else {
                        $mergedRows[$codeKey] = $normalizedRow;
                    }

                    foreach (array_keys($normalizedRow['maquinas']) as $machineColumn) {
                        $detectedMachineColumns[$machineColumn] = $machineColumn;
                    }
                }
            }

            $this->parsedRows = array_values($mergedRows);
            usort($this->parsedRows, fn (array $left, array $right): int => strcmp($left['producto'], $right['producto']));

            $this->machineColumns = array_values($detectedMachineColumns);
            natcasesort($this->machineColumns);
            $this->machineColumns = array_values($this->machineColumns);
            $this->sheetRouteName = $detectedRoute;
            $this->sheetOperatorName = $detectedOperator;

            $matchedProducts = collect($this->parsedRows)
                ->filter(fn (array $row): bool => isset($this->productCatalog[$this->normalizeProductCode($row['cod'])]))
                ->count();

            $matchedMachines = collect($this->machineColumns)
                ->filter(fn (string $column): bool => isset($this->routeMachines[$this->normalizeMachineCode($column)]))
                ->count();

            $this->summaryMessage = 'Planilla procesada con ' . implode(' + ', array_values($providersUsed))
                . ". Filas detectadas: " . count($this->parsedRows)
                . ". Productos reconocidos: {$matchedProducts}."
                . " Máquinas de la ruta reconocidas: {$matchedMachines}.";

            $this->dispatch(
                'driver-stocking-photo-imported',
                rows: $this->buildRowsForBrowser(),
                machineColumns: $this->machineColumns,
                sheetRouteName: $this->sheetRouteName,
                sheetOperatorName: $this->sheetOperatorName,
            );

            $this->dispatchProgress(100, 'Planilla de surtido lista para aplicar a la máquina seleccionada.');
            $this->dispatchToast('success', 'La planilla quedó lista. Selecciona una máquina de la ruta para aplicar sus cantidades.');
        } catch (Throwable $exception) {
            $this->lastError = $exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->first()
                : $exception->getMessage();

            Log::error('driver_stocking_photo_import_failed', [
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

    public function enableManualMode(): void
    {
        $this->authorizeUsage();
        $this->manualMode = true;
        $this->lastError  = null;
    }

    public function disableManualMode(): void
    {
        $this->manualMode = false;
        $this->lastError  = null;
    }

    /**
     * Called from Alpine.js with client-side quantities.
     *
     * @param  array<string, int>  $quantities  normalized product-code → qty
     */
    public function submitManualData(string $machineKey, array $quantities): void
    {
        $this->authorizeUsage();

        $machineKey = trim($machineKey);

        if ($machineKey === '') {
            $this->lastError = 'Selecciona una máquina antes de guardar.';
            $this->dispatchToast('error', $this->lastError);
            return;
        }

        $normalizedKey = $this->normalizeMachineCode($machineKey);
        $machine       = $this->routeMachines[$normalizedKey] ?? null;
        $machineLabel  = $machine !== null ? $machine['code'] : strtoupper($machineKey);

        $rows = [];

        foreach ($quantities as $productCodeKey => $rawQty) {
            $qty = max(0, (int) $rawQty);

            if ($qty === 0) {
                continue;
            }

            $product = $this->productCatalog[(string) $productCodeKey] ?? null;

            if ($product === null) {
                continue;
            }

            $rows[] = [
                'code'        => $product['code'],
                'catalogCode' => $product['code'],
                'product'     => $product['name'],
                'quantities'  => [$normalizedKey => $qty],
            ];
        }

        if ($rows === []) {
            $this->lastError = 'Ingresa al menos una cantidad mayor a cero para continuar.';
            $this->dispatchToast('error', $this->lastError);
            return;
        }

        $this->machineColumns = [$machineLabel];
        $this->parsedRows     = array_map(fn (array $row): array => [
            'cod'      => $row['code'],
            'producto' => $row['product'],
            'maquinas' => $row['quantities'],
        ], $rows);

        $this->summaryMessage = "Entrada manual — Máquina {$machineLabel}. "
            . count($rows) . ' producto(s) con cantidad asignada.';

        $this->dispatch(
            'driver-stocking-photo-imported',
            rows:              $rows,
            machineColumns:    [$machineLabel],
            sheetRouteName:    null,
            sheetOperatorName: auth()->user()?->name,
        );

        $this->dispatchToast('success', "Planilla manual lista para máquina {$machineLabel}.");
        $this->lastError  = null;
        $this->manualMode = false;
    }

    public function render(): View
    {
        return view('livewire.driver.stocking-photo-import');
    }

    private function authorizeUsage(): void
    {
        abort_unless(auth()->user()?->can('stockings.create'), 403);
    }

    /**
     * @return array{payload:array,provider:string}
     */
    private function processWithAvailableProviders(UploadedFile $photo, int $photoNumber, int $photoCount): array
    {
        $attemptErrors = [];
        $attemptedProviders = 0;

        foreach (['local_ocr', 'gemini', 'openai'] as $provider) {
            if (! $this->isProviderConfigured($provider)) {
                continue;
            }

            $attemptedProviders++;

            try {
                $payload = match ($provider) {
                    'local_ocr' => $this->processWithLocalOcr($photo),
                    'gemini' => $this->processWithGemini($photo),
                    'openai' => $this->processWithOpenAi($photo),
                    default => throw new RuntimeException('Proveedor de IA no soportado.'),
                };

                return [
                    'payload' => $payload,
                    'provider' => $provider,
                ];
            } catch (Throwable $exception) {
                $attemptErrors[] = $this->providerDisplayName($provider) . ': ' . $this->friendlyExceptionMessage($provider, $exception);

                Log::warning('driver_stocking_photo_import_provider_failed', [
                    'user_id' => auth()->id(),
                    'provider' => $provider,
                    'photo_number' => $photoNumber,
                    'photo_count' => $photoCount,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                if ($provider === 'local_ocr' && $this->isProviderConfigured('gemini')) {
                    $this->dispatchProgress(
                        min(95, 24 + ($photoNumber * 14)),
                        "OCR local no respondió para la planilla {$photoNumber}. Intentando con Gemini..."
                    );
                }

                if ($provider === 'gemini' && $this->isProviderConfigured('openai')) {
                    $this->dispatchProgress(
                        min(95, 34 + ($photoNumber * 18)),
                        "Gemini no respondió para la planilla {$photoNumber}. Intentando con OpenAI..."
                    );
                }
            }
        }

        if ($attemptedProviders === 0) {
            throw new RuntimeException('Debes habilitar OCR local, GEMINI_API_KEY u OPENAI_API_KEY para procesar planillas de surtido.');
        }

        throw new RuntimeException($this->buildUnavailableProvidersMessage($attemptErrors));
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
                            ['text' => $this->buildPrompt()],
                            ['inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => base64_encode($binary),
                            ]],
                        ],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                        'responseJsonSchema' => $this->responseSchema(),
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
                throw new RuntimeException('Gemini no devolvió contenido estructurado para la planilla.');
            }

            $decoded = json_decode($this->extractJsonPayload($jsonText), true);

            if (! is_array($decoded) || ! isset($decoded['filas']) || ! is_array($decoded['filas'])) {
                throw new RuntimeException('La respuesta de Gemini no contiene el JSON esperado.');
            }

            return $decoded;
        }

        throw $lastException ?? new RuntimeException('Gemini no respondió con un modelo disponible para procesar la planilla.');
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
                        ['type' => 'input_text', 'text' => $this->buildPrompt()],
                        ['type' => 'input_image', 'image_url' => $imageDataUrl, 'detail' => 'high'],
                    ],
                ]],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'driver_stocking_sheet',
                        'strict' => false,
                        'schema' => $this->responseSchema(),
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

    private function processWithLocalOcr(UploadedFile $photo): array
    {
        $endpoint = rtrim((string) config('services.local_ocr.endpoint', 'http://127.0.0.1:8011'), '/');
        $timeout = max(15, (int) config('services.local_ocr.timeout', 120));
        $connectTimeout = max(2, (int) config('services.local_ocr.connect_timeout', 5));
        $binary = file_get_contents($photo->getRealPath());

        if ($binary === false) {
            throw new RuntimeException('No fue posible leer una de las imágenes seleccionadas.');
        }

        $response = Http::acceptJson()
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->post("{$endpoint}/ocr/stocking-sheet", [
                'image_base64' => base64_encode($binary),
                'route_name' => $this->routeName,
                'route_code' => $this->routeCode,
                'route_machine_codes' => array_values(array_map(
                    fn (array $machine): string => (string) $machine['code'],
                    $this->routeMachines,
                )),
                'product_catalog' => array_values(array_map(
                    fn (array $product): array => [
                        'code' => (string) $product['code'],
                        'name' => (string) $product['name'],
                    ],
                    $this->productCatalog,
                )),
            ]);

        if (! $response->successful()) {
            $message = $this->extractProviderMessage($response, 'El OCR local devolvió un error al intentar procesar la planilla.');
            throw new RuntimeException($this->formatProviderError('local_ocr', $message, $response->status()));
        }

        $decoded = $response->json();

        if (! is_array($decoded) || ! isset($decoded['filas']) || ! is_array($decoded['filas'])) {
            throw new RuntimeException('El OCR local no devolvió el JSON esperado para la planilla.');
        }

        return $decoded;
    }

    private function buildPrompt(): string
    {
        $routeName = $this->routeName ?: 'sin ruta configurada';
        $routeCode = $this->routeCode ?: 'sin código';
        $machineCodes = array_values(array_map(
            fn (array $machine): string => $this->machinePromptLabel($machine['code']),
            $this->routeMachines,
        ));

        $machineList = $machineCodes === [] ? 'No hay máquinas activas en la ruta.' : implode(', ', $machineCodes);

        return <<<PROMPT
Analiza esta fotografía de una planilla física de surtido para máquinas de ruta.

Contexto de la ruta actual:
- Ruta esperada en el sistema: {$routeName}
- Código interno de ruta: {$routeCode}
- Códigos de máquinas activas esperadas en esta ruta: {$machineList}

Objetivo:
- Extraer una tabla confiable en JSON.
- Detectar el nombre de la ruta o encabezado de la planilla si aparece.
- Detectar el nombre del rutero o conductor si aparece.
- Detectar las filas con COD del producto, nombre del producto y las columnas por máquina.

Instrucciones obligatorias:
1. Devuelve únicamente JSON válido, sin markdown y sin texto adicional.
2. Conserva cada fila real de producto detectada.
3. Si un valor numérico es dudoso pero legible, devuelve el entero más probable.
4. Si una celda por máquina está vacía, devuelve 0.
5. Si una fila no tiene código claro, omítela.
6. Normaliza los códigos de producto como texto sin espacios extra.
7. Normaliza el nombre del producto sin basura de OCR.
8. Las claves de "maquinas" deben corresponder al identificador visible de la columna de máquina.
9. Prioriza como columnas válidas de máquina las que coincidan con los códigos esperados de la ruta.
10. No inventes productos ni máquinas que no aparezcan en la imagen.

Formato obligatorio:
{
  "ruta_detectada": "6 Abril",
  "rutero_detectado": "Osvaldo",
  "filas": [
    {
      "cod": "732",
      "producto": "ROSCAS BUNGA",
      "maquinas": {
        "48": 16,
        "16": 83
      }
    }
  ]
}
PROMPT;
    }

    private function responseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'ruta_detectada' => ['type' => 'string'],
                'rutero_detectado' => ['type' => 'string'],
                'filas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'cod' => ['type' => 'string'],
                            'producto' => ['type' => 'string'],
                            'maquinas' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'integer',
                                    'minimum' => 0,
                                ],
                            ],
                        ],
                        'required' => ['cod', 'producto', 'maquinas'],
                    ],
                ],
            ],
            'required' => ['ruta_detectada', 'rutero_detectado', 'filas'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{cod:string,producto:string,maquinas:array<string,int>}
     */
    private function normalizeRow(array $row): array
    {
        $machines = [];

        foreach ((array) ($row['maquinas'] ?? []) as $column => $value) {
            $normalizedColumn = strtoupper(trim((string) $column));

            if ($normalizedColumn === '') {
                continue;
            }

            $machines[$normalizedColumn] = max(0, (int) $value);
        }

        return [
            'cod' => strtoupper(trim((string) ($row['cod'] ?? ''))),
            'producto' => trim((string) ($row['producto'] ?? '')),
            'maquinas' => $machines,
        ];
    }

    /**
     * @param  array{cod:string,producto:string,maquinas:array<string,int>}  $original
     * @param  array{cod:string,producto:string,maquinas:array<string,int>}  $replacement
     * @return array{cod:string,producto:string,maquinas:array<string,int>}
     */
    private function mergeRows(array $original, array $replacement): array
    {
        return [
            'cod' => $original['cod'],
            'producto' => $replacement['producto'] !== '' ? $replacement['producto'] : $original['producto'],
            'maquinas' => array_merge($original['maquinas'], $replacement['maquinas']),
        ];
    }

    /**
     * @return array<int, array{code:string,product:string,quantities:array<string,int>,catalogCode:?string}>
     */
    private function buildRowsForBrowser(): array
    {
        return array_values(array_map(function (array $row): array {
            $catalogProduct = $this->productCatalog[$this->normalizeProductCode($row['cod'])] ?? null;
            $normalizedQuantities = [];

            foreach ((array) ($row['maquinas'] ?? []) as $machineCode => $quantity) {
                $normalizedMachineCode = $this->normalizeMachineCode((string) $machineCode);

                if ($normalizedMachineCode === '') {
                    continue;
                }

                $normalizedQuantities[$normalizedMachineCode] = max(0, (int) $quantity);
            }

            return [
                'code' => $row['cod'],
                'catalogCode' => $catalogProduct['code'] ?? null,
                'product' => $row['producto'],
                'quantities' => $normalizedQuantities,
            ];
        }, $this->parsedRows));
    }

    private function normalizeProductCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', trim($code)));
    }

    private function normalizeMachineCode(string $code): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', trim($code)));

        if ($normalized === '') {
            return '';
        }

        if (preg_match('/(\d+)/', $normalized, $matches) === 1) {
            return ltrim($matches[1], '0') ?: '0';
        }

        return $normalized;
    }

    private function machinePromptLabel(string $code): string
    {
        $rawCode = strtoupper(trim($code));
        $normalizedCode = $this->normalizeMachineCode($code);

        if ($rawCode === '' || $rawCode === $normalizedCode) {
            return $rawCode !== '' ? $rawCode : $normalizedCode;
        }

        return "{$rawCode} (visible también como {$normalizedCode})";
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
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

        throw new RuntimeException('OpenAI no devolvió contenido estructurado para la planilla.');
    }

    private function isProviderConfigured(string $provider): bool
    {
        if ($provider === 'local_ocr') {
            return (bool) config('services.local_ocr.enabled', false);
        }

        return trim((string) config("services.{$provider}.api_key")) !== '';
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
            'local_ocr' => 'OCR local',
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

        return in_array($status, [403, 404, 429, 500, 503], true)
            || Str::contains($normalizedMessage, ['high demand', 'temporarily unavailable', 'overloaded', 'try again later', 'not found', 'model not found']);
    }

    private function formatProviderError(string $provider, string $message, ?int $status = null): string
    {
        $normalizedMessage = Str::lower($message);

        if ($provider === 'local_ocr') {
            if ($status === 503 && Str::contains($normalizedMessage, ['ollama_model configurado', 'requiere ollama_model'])) {
                return 'El OCR local está iniciado, pero falta configurar OLLAMA_MODEL con un modelo de vision en el servicio Python.';
            }

            if ($status === 503 || Str::contains($normalizedMessage, ['ollama', 'ocr local', 'conectar'])) {
                return 'El OCR local no está disponible. Verifica que el servicio Python y Ollama estén iniciados.';
            }

            if ($status === 422) {
                return 'El OCR local no pudo detectar filas válidas en esta planilla.';
            }
        }

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

    private function dispatchProgress(int $progress, string $message): void
    {
        $this->processingMessage = $message;
        $this->dispatch('driver-stocking-photo-progress', progress: $progress, message: $message);
    }

    private function dispatchToast(string $type, string $message): void
    {
        $this->dispatch('driver-stocking-photo-toast', type: $type, message: $message);
    }

    private function resetState(): void
    {
        $this->lastError = null;
        $this->summaryMessage = null;
        $this->sheetRouteName = null;
        $this->sheetOperatorName = null;
        $this->parsedRows = [];
        $this->machineColumns = [];
    }
}
