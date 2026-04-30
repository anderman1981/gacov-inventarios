<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Models\Product;
use App\Models\PurchaseImportBatch;
use App\Models\PurchaseImportRow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UpdatePurchaseImportRowHandler
{
    /**
     * @param array{
     *     product_code:string,
     *     quantity:int|string,
     *     unit_cost?:float|int|string|null,
     *     supplier?:string|null,
     *     invoice_number?:string|null,
     *     purchase_date?:string|null,
     *     notes?:string|null
     * } $data
     */
    public function handle(PurchaseImportBatch $batch, PurchaseImportRow $row, array $data): PurchaseImportBatch
    {
        if ((int) $row->purchase_import_batch_id !== (int) $batch->id) {
            throw new RuntimeException('La fila no pertenece a esta compra temporal.');
        }

        if ($batch->status !== 'borrador') {
            throw new RuntimeException('Solo se pueden editar compras temporales en borrador.');
        }

        return DB::transaction(function () use ($batch, $row, $data): PurchaseImportBatch {
            $lockedBatch = PurchaseImportBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== 'borrador') {
                throw new RuntimeException('Solo se pueden editar compras temporales en borrador.');
            }

            $lockedRow = PurchaseImportRow::query()
                ->whereKey($row->id)
                ->where('purchase_import_batch_id', $lockedBatch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $productCode = trim((string) $data['product_code']);
            $product = $this->resolveProduct($productCode);
            $errors = [];

            if (! $product instanceof Product) {
                $errors[] = "Producto no encontrado para {$productCode}.";
            }

            $quantity = (int) $data['quantity'];
            $unitCost = $this->toFloat($data['unit_cost'] ?? 0);

            $lockedRow->update([
                'product_id' => $product?->id,
                'product_code' => $productCode,
                'product_name' => $product?->name ?? $lockedRow->product_name,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'supplier' => $this->cleanNullableString($data['supplier'] ?? null),
                'invoice_number' => $this->cleanNullableString($data['invoice_number'] ?? null),
                'purchase_date' => $this->parseDate($data['purchase_date'] ?? null),
                'notes' => $this->cleanNullableString($data['notes'] ?? null),
                'status' => $errors === [] ? 'valida' : 'error',
                'error_message' => $errors !== [] ? implode(' ', $errors) : null,
            ]);

            $this->recalculateBatch($lockedBatch);

            return $lockedBatch->refresh()->load('rows.product', 'warehouse', 'uploader');
        });
    }

    private function resolveProduct(string $productCode): ?Product
    {
        return Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($productCode): void {
                $query->where('code', $productCode)
                    ->orWhere('worldoffice_code', $productCode)
                    ->orWhere('supplier_sku', $productCode);
            })
            ->first();
    }

    private function recalculateBatch(PurchaseImportBatch $batch): void
    {
        $rows = $batch->rows()->get();
        $validRows = $rows->where('status', 'valida');

        $batch->update([
            'total_rows' => $rows->count(),
            'valid_rows' => $validRows->count(),
            'error_rows' => $rows->where('status', 'error')->count(),
            'total_units' => $validRows->sum('quantity'),
            'total_cost' => $validRows->sum(fn (PurchaseImportRow $row): float => (float) $row->quantity * (float) $row->unit_cost),
        ]);
    }

    private function cleanNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim((string) $value);

        return $cleaned !== '' ? $cleaned : null;
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $cleaned = $this->cleanNullableString($value);

        return $cleaned !== null ? Carbon::parse($cleaned)->startOfDay() : null;
    }
}
