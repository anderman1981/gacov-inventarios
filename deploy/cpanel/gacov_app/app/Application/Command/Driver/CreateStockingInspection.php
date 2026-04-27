<?php

declare(strict_types=1);

namespace App\Application\Command\Driver;

use App\Models\Machine;
use App\Models\MachineStockingItem;
use App\Models\MachineStockingRecord;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fase 1 del surtido: el conductor inspecciona la máquina e indica qué necesita.
 * NO descuenta stock. NO muestra cantidades del vehículo al conductor.
 * SÍ valida que el vehículo tenga el producto disponible (qty > 0) antes de aceptar la solicitud.
 *
 * @throws \RuntimeException si el vehículo no tiene un producto solicitado
 */
final class CreateStockingInspection
{
    /**
     * @param  Collection<int|string, array{quantity: int, notes?: ?string}>  $items
     *
     * @throws \RuntimeException
     */
    public function handle(
        User $user,
        Route $route,
        Machine $machine,
        Warehouse $vehicleWarehouse,
        Collection $items,
        ?string $notes,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $geolocationAccuracy = null,
    ): MachineStockingRecord {
        // Validar disponibilidad sin revelar cantidades exactas
        $this->assertProductsAvailableInVehicle($vehicleWarehouse, $items);

        return DB::transaction(function () use (
            $user, $route, $machine, $vehicleWarehouse, $items, $notes,
            $latitude, $longitude, $geolocationAccuracy
        ): MachineStockingRecord {
            $code = 'SURT-' . strtoupper(substr(uniqid(), -6));

            $record = MachineStockingRecord::create([
                'code'                 => $code,
                'machine_id'           => $machine->id,
                'route_id'             => $route->id,
                'vehicle_warehouse_id' => $vehicleWarehouse->id,
                'performed_by'         => $user->id,
                'status'               => 'pendiente_carga',
                'notes'                => $notes,
                'latitude'             => $latitude,
                'longitude'            => $longitude,
                'geolocation_accuracy' => $geolocationAccuracy,
                'was_offline'          => false,
                'started_at'           => now(),
            ]);

            foreach ($items as $productId => $item) {
                $qty = (int) $item['quantity'];

                MachineStockingItem::create([
                    'stocking_record_id'   => $record->id,
                    'product_id'           => (int) $productId,
                    'quantity_loaded'      => $qty,
                    'physical_count_before' => null,
                    'physical_count_after'  => null,
                    'notes'                => filled($item['notes'] ?? null) ? trim((string) $item['notes']) : null,
                ]);
            }

            return $record;
        });
    }

    /**
     * Valida que el vehículo tenga cada producto con al menos 1 unidad disponible.
     * No revela la cantidad exacta al conductor — solo permite o bloquea.
     *
     * @param  Collection<int|string, array{quantity: int, notes?: ?string}>  $items
     *
     * @throws \RuntimeException
     */
    private function assertProductsAvailableInVehicle(Warehouse $vehicleWarehouse, Collection $items): void
    {
        foreach ($items as $productId => $item) {
            $available = (int) (Stock::where('warehouse_id', $vehicleWarehouse->id)
                ->where('product_id', $productId)
                ->value('quantity') ?? 0);

            $requested = (int) $item['quantity'];

            if ($requested <= 0) {
                continue;
            }

            if ($available <= 0) {
                $productName = Product::find($productId)?->name ?? "producto #{$productId}";
                throw new \RuntimeException(
                    "El producto «{$productName}» no está disponible en el vehículo."
                );
            }

            if ($available < $requested) {
                $productName = Product::find($productId)?->name ?? "producto #{$productId}";
                throw new \RuntimeException(
                    "No hay suficiente stock en el vehículo para «{$productName}». " .
                    "Reduce la cantidad solicitada."
                );
            }
        }
    }
}
