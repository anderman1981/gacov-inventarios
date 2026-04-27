<?php

declare(strict_types=1);

namespace App\Application\Command\Driver;

use App\Models\Machine;
use App\Models\MachineSale;
use App\Models\MachineSaleItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RegisterSale
{
    /**
     * Registra una venta desde una máquina.
     *
     * @param  Collection<int|string, array{quantity: int, unit_price: float, notes?: ?string}>  $items
     *
     * @throws \RuntimeException si no hay stock suficiente en la máquina
     */
    public function handle(
        User $user,
        Machine $machine,
        Warehouse $machineWarehouse,
        Collection $items,
        ?string $notes,
    ): MachineSale {
        $this->assertSufficientMachineStock($machineWarehouse, $items);

        return DB::transaction(function () use (
            $user, $machine, $machineWarehouse, $items, $notes
        ): MachineSale {
            $code = 'VENTA-'.strtoupper(substr(uniqid(), -6));

            $sale = MachineSale::create([
                'code' => $code,
                'machine_id' => $machine->id,
                'registered_by' => $user->id,
                'status' => 'completado',
                'sale_date' => today(),
                'notes' => $notes,
                'was_offline' => false,
            ]);

            foreach ($items as $productId => $item) {
                $qty = (int) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                MachineSaleItem::create([
                    'machine_sale_id' => $sale->id,
                    'product_id' => (int) $productId,
                    'quantity_sold' => $qty,
                    'unit_price' => $unitPrice,
                    'notes' => filled($item['notes'] ?? null) ? trim((string) $item['notes']) : null,
                ]);

                $stock = Stock::firstOrNew(['warehouse_id' => $machineWarehouse->id, 'product_id' => (int) $productId]);
                $stock->quantity = ($stock->quantity ?? 0) - $qty;
                $stock->save();

                StockMovement::create([
                    'product_id' => (int) $productId,
                    'origin_warehouse_id' => $machineWarehouse->id,
                    'movement_type' => 'venta_maquina',
                    'quantity' => $qty,
                    'unit_cost' => $unitPrice,
                    'reference_code' => $code,
                    'notes' => "Venta en máquina {$machine->code}",
                    'performed_by' => $user->id,
                ]);
            }

            return $sale;
        });
    }

    /**
     * @param  Collection<int|string, array{quantity: int, unit_price: float, notes?: ?string}>  $items
     *
     * @throws \RuntimeException
     */
    private function assertSufficientMachineStock(Warehouse $machineWarehouse, Collection $items): void
    {
        foreach ($items as $productId => $item) {
            $available = (int) (Stock::where('warehouse_id', $machineWarehouse->id)
                ->where('product_id', $productId)
                ->value('quantity') ?? 0);

            $requested = (int) $item['quantity'];

            if ($available < $requested) {
                $productName = Product::find($productId)?->name ?? "producto #{$productId}";

                throw new \RuntimeException(
                    "Stock insuficiente en máquina para «{$productName}». Disponible: {$available}, vendido: {$requested}."
                );
            }
        }
    }
}
