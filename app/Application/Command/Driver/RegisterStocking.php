<?php

declare(strict_types=1);

namespace App\Application\Command\Driver;

use App\Models\Machine;
use App\Models\MachineStockingItem;
use App\Models\MachineStockingRecord;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RegisterStocking
{
    /**
     * Registra un surtido desde el vehículo hacia una máquina.
     *
     * @param  Collection<int|string, array{quantity: int}>  $items
     *
     * @throws \RuntimeException si no hay stock suficiente en el vehículo
     */
    public function handle(
        User $user,
        Route $route,
        Machine $machine,
        Warehouse $vehicleWarehouse,
        Warehouse $machineWarehouse,
        Collection $items,
        ?string $notes,
    ): MachineStockingRecord {
        $this->assertSufficientVehicleStock($vehicleWarehouse, $items);

        return DB::transaction(function () use (
            $user, $route, $machine, $vehicleWarehouse, $machineWarehouse, $items, $notes
        ): MachineStockingRecord {
            $code = 'SURT-'.strtoupper(substr(uniqid(), -6));

            $record = MachineStockingRecord::create([
                'code' => $code,
                'machine_id' => $machine->id,
                'route_id' => $route->id,
                'vehicle_warehouse_id' => $vehicleWarehouse->id,
                'performed_by' => $user->id,
                'status' => 'completado',
                'notes' => $notes,
                'was_offline' => false,
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            foreach ($items as $productId => $item) {
                $qty = (int) $item['quantity'];

                MachineStockingItem::create([
                    'stocking_record_id' => $record->id,
                    'product_id' => (int) $productId,
                    'quantity_loaded' => $qty,
                ]);

                $vs = Stock::firstOrNew(['warehouse_id' => $vehicleWarehouse->id, 'product_id' => (int) $productId]);
                $vs->quantity = ($vs->quantity ?? 0) - $qty;
                $vs->save();

                $ms = Stock::firstOrNew(['warehouse_id' => $machineWarehouse->id, 'product_id' => (int) $productId]);
                $ms->quantity = ($ms->quantity ?? 0) + $qty;
                $ms->save();

                StockMovement::create([
                    'product_id' => (int) $productId,
                    'origin_warehouse_id' => $vehicleWarehouse->id,
                    'destination_warehouse_id' => $machineWarehouse->id,
                    'movement_type' => 'surtido_maquina',
                    'quantity' => $qty,
                    'reference_code' => $code,
                    'notes' => "Surtido máquina {$machine->code}",
                    'performed_by' => $user->id,
                ]);
            }

            return $record;
        });
    }

    /**
     * @param  Collection<int|string, array{quantity: int}>  $items
     *
     * @throws \RuntimeException
     */
    private function assertSufficientVehicleStock(Warehouse $vehicleWarehouse, Collection $items): void
    {
        foreach ($items as $productId => $item) {
            $available = (int) (Stock::where('warehouse_id', $vehicleWarehouse->id)
                ->where('product_id', $productId)
                ->value('quantity') ?? 0);

            $requested = (int) $item['quantity'];

            if ($available < $requested) {
                $productName = Product::find($productId)?->name ?? "producto #{$productId}";

                throw new \RuntimeException(
                    "Stock insuficiente en vehículo para «{$productName}». Disponible: {$available}, solicitado: {$requested}."
                );
            }
        }
    }
}
