<?php

declare(strict_types=1);

namespace App\Application\Command\Driver;

use App\Models\Machine;
use App\Models\MachineStockingRecord;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\LowVehicleStockNotification;
use App\Notifications\MachineStockedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fase 3 del surtido: el conductor confirma que la máquina fue surtida.
 * - Descuenta productos del vehículo y los suma a la máquina.
 * - Registra el efectivo cargado (billetes + monedas).
 * - Crea movimientos de stock.
 * - Envía notificación "Máquina surtida" a Admin y Manager.
 * - Envía alerta de stock bajo si algún producto del vehículo queda ≤ 5.
 *
 * @throws \RuntimeException si no hay stock suficiente en el vehículo al completar
 */
final class CompleteStockingRecord
{
    private const LOW_STOCK_THRESHOLD = 5;

    /** Denominaciones de billetes COP */
    private const BILL_DENOMINATIONS = [
        'bill_100000' => 100000, 'bill_50000' => 50000, 'bill_20000' => 20000,
        'bill_10000'  => 10000,  'bill_5000'  => 5000,  'bill_2000'  => 2000,
    ];

    /** Denominaciones de monedas COP */
    private const COIN_DENOMINATIONS = [
        'coin_1000' => 1000, 'coin_500' => 500, 'coin_200' => 200,
        'coin_100'  => 100,  'coin_50'  => 50,
    ];

    /**
     * @param  array<string, int>  $cashDenominations  billetes y monedas por campo
     */
    public function handle(
        MachineStockingRecord $record,
        Warehouse $machineWarehouse,
        array $cashDenominations = [],
    ): MachineStockingRecord {
        $vehicleWarehouse = $record->vehicleWarehouse;

        if (! $vehicleWarehouse instanceof Warehouse) {
            throw new \RuntimeException('El registro no tiene bodega de vehículo asignada.');
        }

        $items = $record->items()->with('product')->get();

        // Validar stock suficiente para completar
        foreach ($items as $item) {
            $available = (int) (Stock::where('warehouse_id', $vehicleWarehouse->id)
                ->where('product_id', $item->product_id)
                ->value('quantity') ?? 0);

            if ($available < $item->quantity_loaded) {
                $name = $item->product?->name ?? "producto #{$item->product_id}";
                throw new \RuntimeException(
                    "Stock insuficiente en vehículo para «{$name}». " .
                    "Disponible: {$available}, solicitado: {$item->quantity_loaded}."
                );
            }
        }

        return DB::transaction(function () use ($record, $vehicleWarehouse, $machineWarehouse, $items, $cashDenominations): MachineStockingRecord {
            // Calcular totales de efectivo
            $totalBills = 0;
            $totalCoins = 0;
            foreach (self::BILL_DENOMINATIONS as $field => $value) {
                $qty = (int) ($cashDenominations[$field] ?? 0);
                $totalBills += $qty * $value;
            }
            foreach (self::COIN_DENOMINATIONS as $field => $value) {
                $qty = (int) ($cashDenominations[$field] ?? 0);
                $totalCoins += $qty * $value;
            }

            // Actualizar denominaciones y totales en el registro
            $record->fill(array_merge(
                array_map('intval', $cashDenominations),
                [
                    'total_cash_bills' => $totalBills,
                    'total_cash_coins' => $totalCoins,
                    'total_cash'       => $totalBills + $totalCoins,
                    'status'           => 'completado',
                    'completed_at'     => now(),
                ]
            ));
            $record->save();

            // Descontar del vehículo y sumar a la máquina
            foreach ($items as $item) {
                $qty = $item->quantity_loaded;

                $vs = Stock::firstOrNew([
                    'warehouse_id' => $vehicleWarehouse->id,
                    'product_id'   => $item->product_id,
                ]);
                $vs->quantity = max(0, ($vs->quantity ?? 0) - $qty);
                $vs->save();

                $ms = Stock::firstOrNew([
                    'warehouse_id' => $machineWarehouse->id,
                    'product_id'   => $item->product_id,
                ]);
                $ms->quantity = ($ms->quantity ?? 0) + $qty;
                $ms->save();

                StockMovement::create([
                    'product_id'             => $item->product_id,
                    'origin_warehouse_id'    => $vehicleWarehouse->id,
                    'destination_warehouse_id' => $machineWarehouse->id,
                    'movement_type'          => 'surtido_maquina',
                    'quantity'               => $qty,
                    'reference_code'         => $record->code,
                    'notes'                  => "Surtido máquina {$record->machine?->code} — Fase completada",
                    'performed_by'           => $record->performed_by,
                ]);
            }

            // Notificar a Admin y Manager
            $this->notifyStockingCompleted($record);

            // Verificar y notificar stock bajo en vehículo
            $this->checkLowVehicleStock($vehicleWarehouse, $record->route, $items);

            return $record->fresh(['machine', 'items.product', 'user', 'route']);
        });
    }

    private function notifyStockingCompleted(MachineStockingRecord $record): void
    {
        $machine   = $record->machine;
        $conductor = $record->user;

        if (! $machine || ! $conductor) {
            return;
        }

        $recipients = $this->getAdminAndManagerUsers($record);

        foreach ($recipients as $user) {
            try {
                $user->notify(new MachineStockedNotification($record, $machine, $conductor));
            } catch (\Throwable) {
                // No fallar el surtido por error de notificación
            }
        }
    }

    /**
     * @param  Collection<int, \App\Models\MachineStockingItem>  $items
     */
    private function checkLowVehicleStock(Warehouse $vehicleWarehouse, ?Route $route, Collection $items): void
    {
        if (! $route) {
            return;
        }

        $lowItems = [];

        foreach ($items as $item) {
            $remaining = (int) (Stock::where('warehouse_id', $vehicleWarehouse->id)
                ->where('product_id', $item->product_id)
                ->value('quantity') ?? 0);

            if ($remaining <= self::LOW_STOCK_THRESHOLD) {
                $lowItems[] = [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product?->name ?? "producto #{$item->product_id}",
                    'quantity'     => $remaining,
                ];
            }
        }

        if (empty($lowItems)) {
            return;
        }

        // Obtener ruta con sus relaciones para el stub del record
        $routeModel = $route instanceof Route ? $route : Route::find($route->id);
        if (! $routeModel) {
            return;
        }

        $recipients = $this->getAdminAndManagerUsersForRoute($routeModel);

        foreach ($recipients as $user) {
            try {
                $user->notify(new LowVehicleStockNotification($vehicleWarehouse, $routeModel, $lowItems));
            } catch (\Throwable) {
                // No fallar el surtido
            }
        }
    }

    /** @return Collection<int, User> */
    private function getAdminAndManagerUsers(MachineStockingRecord $record): Collection
    {
        $tenantId = $record->tenant_id;

        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->where('is_active', true)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get();
    }

    /** @return Collection<int, User> */
    private function getAdminAndManagerUsersForRoute(Route $route): Collection
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->where('is_active', true)
            ->get();
    }
}
