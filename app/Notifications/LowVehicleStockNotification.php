<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Route;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada a Admin y Manager cuando el stock de un vehículo
 * llega a 5 unidades o menos en algún producto, después de un surtido.
 */
final class LowVehicleStockNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<array{product_id: int, product_name: string, quantity: int}>  $lowItems
     */
    public function __construct(
        private readonly Warehouse $vehicleWarehouse,
        private readonly Route $route,
        private readonly array $lowItems,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $productList = collect($this->lowItems)
            ->map(fn (array $item): string => "{$item['product_name']}: {$item['quantity']} uds.")
            ->implode(', ');

        return [
            'type'          => 'low_vehicle_stock',
            'title'         => "⚠ Stock bajo en vehículo — Ruta {$this->route->name}",
            'message'       => "El vehículo {$this->vehicleWarehouse->name} tiene stock ≤ 5 en: {$productList}",
            'warehouse_id'  => $this->vehicleWarehouse->id,
            'route_id'      => $this->route->id,
            'route_name'    => $this->route->name,
            'low_items'     => $this->lowItems,
            'threshold'     => 5,
        ];
    }
}
