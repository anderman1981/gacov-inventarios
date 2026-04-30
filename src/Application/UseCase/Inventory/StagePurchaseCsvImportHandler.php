<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Models\Product;
use App\Models\PurchaseImportBatch;
use App\Models\PurchaseImportRow;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class StagePurchaseCsvImportHandler
{
    /**
     * @param array{supplier?:string|null,invoice_number?:string|null,purchase_date?:string|null} $defaults
     */
    public function handle(UploadedFile $file, User $user, array $defaults = []): PurchaseImportBatch
    {
        $warehouse = $this->resolveMainWarehouse();
        $storedPath = $file->store('purchase-imports');
        $supplier = $this->cleanNullableString($defaults['supplier'] ?? null);
        $invoiceNumber = $this->cleanNullableString($defaults['invoice_number'] ?? null);
        $purchaseDate = $this->parseDate($defaults['purchase_date'] ?? null);
        $parsedRows = $this->parseCsv($file, $supplier, $invoiceNumber, $purchaseDate);

        return DB::transaction(function () use ($file, $user, $warehouse, $storedPath, $supplier, $invoiceNumber, $purchaseDate, $parsedRows): PurchaseImportBatch {
            $batch = PurchaseImportBatch::query()->create([
                'tenant_id' => $user->tenant_id,
                'uploaded_by' => $user->id,
                'warehouse_id' => $warehouse->id,
                'original_file_name' => $file->getClientOriginalName(),
                'stored_file_path' => $storedPath,
                'status' => 'borrador',
                'supplier' => $supplier,
                'invoice_number' => $invoiceNumber,
                'purchase_date' => $purchaseDate,
                'total_rows' => count($parsedRows),
                'valid_rows' => collect($parsedRows)->where('status', 'valida')->count(),
                'error_rows' => collect($parsedRows)->where('status', 'error')->count(),
                'total_units' => collect($parsedRows)->where('status', 'valida')->sum('quantity'),
                'total_cost' => collect($parsedRows)
                    ->where('status', 'valida')
                    ->sum(fn (array $row): float => (float) $row['quantity'] * (float) $row['unit_cost']),
            ]);

            foreach ($parsedRows as $row) {
                PurchaseImportRow::query()->create(array_merge($row, [
                    'tenant_id' => $user->tenant_id,
                    'purchase_import_batch_id' => $batch->id,
                ]));
            }

            return $batch->load('rows.product', 'warehouse', 'uploader');
        });
    }

    private function resolveMainWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()
            ->where('type', 'bodega')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $warehouse instanceof Warehouse) {
            throw new RuntimeException('No existe una bodega principal activa para recibir compras.');
        }

        return $warehouse;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseCsv(UploadedFile $file, ?string $defaultSupplier, ?string $defaultInvoice, ?Carbon $defaultDate): array
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException('No fue posible leer el archivo CSV.');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false || count($lines) === 0) {
            throw new RuntimeException('El CSV está vacío.');
        }

        $delimiter = $this->detectDelimiter($lines);
        $headers = array_map(
            fn (?string $header): string => $this->normalizeHeader((string) $header),
            str_getcsv((string) $lines[0], $delimiter)
        );

        if (! in_array('codigo_producto', $headers, true) && ! in_array('codigo', $headers, true) && ! in_array('sku', $headers, true)) {
            throw new RuntimeException('El CSV debe incluir la columna codigo_producto.');
        }

        if (! in_array('cantidad', $headers, true)) {
            throw new RuntimeException('El CSV debe incluir la columna cantidad.');
        }

        $rows = [];

        foreach (array_slice($lines, 1) as $offset => $line) {
            $values = str_getcsv((string) $line, $delimiter);
            $data = [];

            foreach ($headers as $index => $header) {
                $data[$header] = $this->cleanNullableString($values[$index] ?? null);
            }

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $rows[] = $this->buildRow(
                rowNumber: $offset + 2,
                data: $data,
                defaultSupplier: $defaultSupplier,
                defaultInvoice: $defaultInvoice,
                defaultDate: $defaultDate,
            );
        }

        if ($rows === []) {
            throw new RuntimeException('El CSV no contiene filas de compra para revisar.');
        }

        return $rows;
    }

    /**
     * @param array<string, string|null> $data
     * @return array<string, mixed>
     */
    private function buildRow(int $rowNumber, array $data, ?string $defaultSupplier, ?string $defaultInvoice, ?Carbon $defaultDate): array
    {
        $productCode = $this->firstValue($data, ['codigo_producto', 'codigo', 'sku', 'referencia']);
        $quantity = $this->parseInteger($this->firstValue($data, ['cantidad', 'unidades', 'qty']));
        $unitCost = $this->parseDecimal($this->firstValue($data, ['costo_unitario', 'costo', 'valor_unitario', 'unit_cost']));
        $supplier = $this->firstValue($data, ['proveedor', 'supplier']) ?? $defaultSupplier;
        $invoice = $this->firstValue($data, ['factura', 'numero_factura', 'invoice', 'referencia_compra']) ?? $defaultInvoice;
        $purchaseDate = $this->parseDate($this->firstValue($data, ['fecha_compra', 'fecha', 'purchase_date'])) ?? $defaultDate;
        $notes = $this->firstValue($data, ['observaciones', 'notas', 'notes']);
        $errors = [];
        $product = null;

        if ($productCode === null) {
            $errors[] = 'Falta codigo_producto.';
        } else {
            $product = Product::query()
                ->where('is_active', true)
                ->where(function ($query) use ($productCode): void {
                    $query->where('code', $productCode)
                        ->orWhere('worldoffice_code', $productCode)
                        ->orWhere('supplier_sku', $productCode);
                })
                ->first();

            if (! $product instanceof Product) {
                $errors[] = "Producto no encontrado para {$productCode}.";
            }
        }

        if ($quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor a cero.';
        }

        if ($unitCost < 0) {
            $errors[] = 'El costo unitario no puede ser negativo.';
        }

        return [
            'row_number' => $rowNumber,
            'product_id' => $product?->id,
            'product_code' => $productCode ?? '',
            'product_name' => $product?->name ?? $this->firstValue($data, ['nombre_producto', 'producto', 'nombre']),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'supplier' => $supplier,
            'invoice_number' => $invoice,
            'purchase_date' => $purchaseDate,
            'notes' => $notes,
            'status' => $errors === [] ? 'valida' : 'error',
            'error_message' => $errors !== [] ? implode(' ', $errors) : null,
        ];
    }

    /**
     * @param list<string> $lines
     */
    private function detectDelimiter(array $lines): string
    {
        $firstLine = (string) ($lines[0] ?? '');
        $candidates = [';' => 0, ',' => 0, "\t" => 0];

        foreach (array_keys($candidates) as $delimiter) {
            $candidates[$delimiter] = count(str_getcsv($firstLine, $delimiter));
        }

        arsort($candidates);

        return (string) array_key_first($candidates);
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim(str_replace("\xEF\xBB\xBF", '', $header));
        $header = mb_strtolower($header);
        $header = strtr($header, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
        ]);

        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $header), '_');
    }

    /**
     * @param array<string, string|null> $data
     */
    private function rowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, string|null> $data
     * @param list<string> $keys
     */
    private function firstValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->cleanNullableString($data[$key] ?? null);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function cleanNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim((string) $value);

        return $cleaned !== '' ? $cleaned : null;
    }

    private function parseInteger(?string $value): int
    {
        $number = $this->parseDecimal($value);

        return (int) round($number);
    }

    private function parseDecimal(?string $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $cleaned = preg_replace('/[^0-9,\.\-]/', '', trim($value)) ?? '';

        if (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (str_contains($cleaned, ',')) {
            $cleaned = str_replace(',', '.', $cleaned);
        }

        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    private function parseDate(?string $value): ?Carbon
    {
        $cleaned = $this->cleanNullableString($value);

        if ($cleaned === null) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $cleaned);
            } catch (\Throwable) {
                continue;
            }

            if ($date instanceof Carbon && $date->format($format) === $cleaned) {
                return $date->startOfDay();
            }
        }

        try {
            return Carbon::parse($cleaned)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
