<?php

declare(strict_types=1);

namespace App\Support\Inventory;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

final class InventoryAdjustmentService
{
    public function resolveWarehouse(?int $warehouseId): Warehouse
    {
        $warehouse = $warehouseId !== null && $warehouseId > 0
            ? Warehouse::query()->whereKey($warehouseId)->firstOrFail()
            : Warehouse::query()->where('type', 'bodega')->firstOrFail();

        abort_unless(in_array($warehouse->type, ['bodega', 'vehiculo', 'maquina'], true), 404);

        return $warehouse;
    }

    public function hasInventoryActivity(Warehouse $warehouse): bool
    {
        return $warehouse->stocks()
            ->where('quantity', '>', 0)
            ->exists()
            || \App\Models\StockMovement::query()
                ->where(function ($query) use ($warehouse): void {
                    $query->where('origin_warehouse_id', $warehouse->id)
                        ->orWhere('destination_warehouse_id', $warehouse->id);
                })
                ->exists();
    }

    /**
     * @return array{
     *     is_initial_load: bool,
     *     requires_reason: bool,
     *     eyebrow: string,
     *     title: string,
     *     subtitle: string,
     *     submit_label: string,
     *     back_route: string,
     *     back_label: string,
     *     reason_label: string,
     *     reason_help: string,
     *     success_prefix: string
     * }
     */
    public function contextFor(Warehouse $warehouse): array
    {
        $isInitialLoad = ! $this->hasInventoryActivity($warehouse);

        return match ($warehouse->type) {
            'vehiculo' => [
                'is_initial_load' => $isInitialLoad,
                'requires_reason' => ! $isInitialLoad,
                'eyebrow' => $isInitialLoad ? 'Carga inicial de vehículo' : 'Reposición de vehículo',
                'title' => 'Carga de mercancía para vehículo',
                'subtitle' => 'Agrega mercancía a la bodega móvil de la ruta. La primera carga se registra como inventario inicial; los ajustes posteriores dejan trazabilidad.',
                'submit_label' => $isInitialLoad ? 'Registrar carga inicial' : 'Guardar recarga del vehículo',
                'back_route' => 'inventory.vehicles',
                'back_label' => 'Volver a vehículos',
                'reason_label' => 'Observación del ajuste',
                'reason_help' => 'Explica por qué cambió el inventario del vehículo. Este ajuste quedará notificado al admin.',
                'success_prefix' => $isInitialLoad ? 'Carga inicial registrada en el vehículo' : 'Mercancía actualizada en el vehículo',
            ],
            'maquina' => [
                'is_initial_load' => $isInitialLoad,
                'requires_reason' => ! $isInitialLoad,
                'eyebrow' => $isInitialLoad ? 'Carga inicial de máquina' : 'Corrección de máquina',
                'title' => $isInitialLoad ? 'Carga inicial de máquina' : 'Corrección de inventario de máquina',
                'subtitle' => 'Define el inventario real de esta máquina. La primera carga inicializa la bodega; cualquier corrección posterior exige observación para control administrativo.',
                'submit_label' => $isInitialLoad ? 'Registrar carga inicial' : 'Guardar corrección de máquina',
                'back_route' => 'inventory.machines',
                'back_label' => 'Volver a máquinas',
                'reason_label' => 'Observación del ajuste',
                'reason_help' => 'Describe la novedad: diferencia de conteo, devolución, merma o cualquier motivo del ajuste. Se notificará al admin.',
                'success_prefix' => $isInitialLoad ? 'Carga inicial registrada en la máquina' : 'Inventario corregido en la máquina',
            ],
            default => [
                'is_initial_load' => $isInitialLoad,
                'requires_reason' => ! $isInitialLoad,
                'eyebrow' => $isInitialLoad ? 'Carga inicial' : 'Corrección manual',
                'title' => 'Ajuste de stock',
                'subtitle' => 'Usa esta pantalla para corregir el inventario final de la bodega principal después de un conteo físico o una novedad operacional.',
                'submit_label' => $isInitialLoad ? 'Registrar carga inicial' : 'Aplicar ajuste',
                'back_route' => 'inventory.warehouse',
                'back_label' => 'Volver a bodega',
                'reason_label' => 'Motivo del ajuste',
                'reason_help' => 'Explica por qué cambió el inventario. Este ajuste queda trazado para control administrativo.',
                'success_prefix' => 'Stock ajustado en la bodega',
            ],
        };
    }

    public function shouldNotifyAdmins(bool $requiresReason, ?User $actor, int $difference): bool
    {
        if ($difference === 0 || $actor === null) {
            return false;
        }

        if (! $requiresReason) {
            return false;
        }

        return ! $actor->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * @return Collection<int, User>
     */
    public function adminRecipients(?int $excludedUserId = null): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->when($excludedUserId !== null, fn ($query) => $query->whereKeyNot($excludedUserId))
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'super_admin']))
            ->get();
    }
}
