<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class InventoryAdjustmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $actor,
        private readonly Warehouse $warehouse,
        private readonly Product $product,
        private readonly int $oldQuantity,
        private readonly int $newQuantity,
        private readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $warehouseType = match ($this->warehouse->type) {
            'vehiculo' => 'vehículo',
            'maquina' => 'máquina',
            default => 'bodega',
        };

        $difference = $this->newQuantity - $this->oldQuantity;
        $direction = $difference >= 0 ? 'subió' : 'bajó';

        return [
            'title' => 'Ajuste de inventario pendiente de revisión',
            'message' => "{$this->actor->name} ajustó {$this->product->name} en {$warehouseType} {$this->warehouse->name}. El inventario {$direction} de {$this->oldQuantity} a {$this->newQuantity}.",
            'reason' => $this->reason,
            'warehouse_id' => $this->warehouse->id,
            'warehouse_name' => $this->warehouse->name,
            'warehouse_type' => $warehouseType,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'actor_name' => $this->actor->name,
            'old_quantity' => $this->oldQuantity,
            'new_quantity' => $this->newQuantity,
            'difference' => $difference,
            'action_url' => route('inventory.movements'),
        ];
    }
}
