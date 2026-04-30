<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Models\Product;
use App\Models\PurchaseImportBatch;
use App\Models\PurchaseImportRow;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ConfirmPurchaseImportHandler
{
    public function handle(PurchaseImportBatch $batch, User $user): PurchaseImportBatch
    {
        if (! $batch->canBeConfirmed()) {
            throw new RuntimeException('La compra solo se puede cargar cuando todas las filas temporales están válidas.');
        }

        return DB::transaction(function () use ($batch, $user): PurchaseImportBatch {
            $lockedBatch = PurchaseImportBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedBatch->canBeConfirmed()) {
                throw new RuntimeException('La compra ya fue procesada, descartada o tiene errores pendientes.');
            }

            $warehouse = Warehouse::query()->whereKey($lockedBatch->warehouse_id)->lockForUpdate()->firstOrFail();
            $rows = $lockedBatch->rows()
                ->with('product')
                ->where('status', 'valida')
                ->orderBy('row_number')
                ->get();

            foreach ($rows as $row) {
                $this->applyRow($row, $warehouse, $lockedBatch, $user);
            }

            $lockedBatch->update([
                'status' => 'procesado',
                'processed_at' => now(),
                'processed_by' => $user->id,
            ]);

            return $lockedBatch->refresh()->load('rows.product', 'warehouse', 'uploader', 'processor');
        });
    }

    private function applyRow(PurchaseImportRow $row, Warehouse $warehouse, PurchaseImportBatch $batch, User $user): void
    {
        $product = $row->product;

        if (! $product instanceof Product) {
            throw new RuntimeException("La fila {$row->row_number} no tiene producto válido.");
        }

        $stock = Stock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->first();

        if (! $stock instanceof Stock) {
            $stock = new Stock([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'quantity' => 0,
                'min_quantity' => (int) $product->min_stock_alert,
            ]);
        }

        $stock->quantity = (int) $stock->quantity + (int) $row->quantity;
        $stock->save();

        $productUpdates = [];

        if ((float) $row->unit_cost > 0) {
            $productUpdates['cost'] = $row->unit_cost;
        }

        if ($row->supplier !== null && trim((string) $row->supplier) !== '') {
            $productUpdates['supplier'] = $row->supplier;
        }

        if ($row->purchase_date !== null) {
            $productUpdates['purchase_date'] = $row->purchase_date;
        }

        if ($productUpdates !== []) {
            $product->update($productUpdates);
        }

        StockMovement::query()->create([
            'product_id' => $product->id,
            'origin_warehouse_id' => null,
            'destination_warehouse_id' => $warehouse->id,
            'movement_type' => 'compra',
            'quantity' => (int) $row->quantity,
            'unit_cost' => (float) $row->unit_cost,
            'reference_code' => $this->referenceCode($batch, $row),
            'notes' => $this->movementNotes($batch, $row),
            'performed_by' => $user->id,
        ]);
    }

    private function referenceCode(PurchaseImportBatch $batch, PurchaseImportRow $row): string
    {
        if ($row->invoice_number !== null && trim((string) $row->invoice_number) !== '') {
            return mb_substr('COMPRA-'.$row->invoice_number.'-'.$row->row_number, 0, 50);
        }

        return 'COMPRA-'.$batch->id.'-'.$row->row_number;
    }

    private function movementNotes(PurchaseImportBatch $batch, PurchaseImportRow $row): string
    {
        $parts = ['Compra CSV lote #'.$batch->id];

        if ($row->supplier !== null) {
            $parts[] = 'Proveedor: '.$row->supplier;
        }

        if ($row->notes !== null) {
            $parts[] = $row->notes;
        }

        return implode(' · ', $parts);
    }
}
