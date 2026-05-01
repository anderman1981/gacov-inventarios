<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Models\Product;
use App\Models\PurchaseImportBatch;
use App\Models\PurchaseImportRow;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RevalidatePurchaseImportBatchHandler
{
    public function handle(PurchaseImportBatch $batch): PurchaseImportBatch
    {
        if ($batch->status !== 'borrador') {
            throw new RuntimeException('Solo se pueden verificar compras temporales en borrador.');
        }

        return DB::transaction(function () use ($batch): PurchaseImportBatch {
            $lockedBatch = PurchaseImportBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== 'borrador') {
                throw new RuntimeException('Solo se pueden verificar compras temporales en borrador.');
            }

            $rows = PurchaseImportRow::query()
                ->where('purchase_import_batch_id', $lockedBatch->id)
                ->orderBy('row_number')
                ->lockForUpdate()
                ->get();

            foreach ($rows as $row) {
                $productCode = trim((string) $row->product_code);
                $product = $this->resolveProduct($productCode);
                $errors = [];

                if (! $product instanceof Product) {
                    $errors[] = "Producto no encontrado para {$productCode}.";
                }

                if ((int) $row->quantity <= 0) {
                    $errors[] = 'La cantidad debe ser mayor a cero.';
                }

                if ((float) $row->unit_cost < 0) {
                    $errors[] = 'El costo unitario no puede ser negativo.';
                }

                $row->update([
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? $row->product_name,
                    'status' => $errors === [] ? 'valida' : 'error',
                    'error_message' => $errors !== [] ? implode(' ', $errors) : null,
                ]);
            }

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
}
