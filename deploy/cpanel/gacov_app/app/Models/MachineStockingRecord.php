<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MachineStockingRecord extends Model
{
    use BelongsToTenant;

    /** Status del flujo de surtido por fases */
    public const STATUS_PENDIENTE_CARGA = 'pendiente_carga';
    public const STATUS_EN_SURTIDO      = 'en_surtido';
    public const STATUS_COMPLETADO      = 'completado';
    public const STATUS_CANCELADO       = 'cancelado';

    public const BILL_DENOMINATIONS = [
        'bill_100000' => 100000, 'bill_50000' => 50000, 'bill_20000' => 20000,
        'bill_10000'  => 10000,  'bill_5000'  => 5000,  'bill_2000'  => 2000,
    ];

    public const COIN_DENOMINATIONS = [
        'coin_1000' => 1000, 'coin_500' => 500, 'coin_200' => 200,
        'coin_100'  => 100,  'coin_50'  => 50,
    ];

    protected $fillable = [
        'tenant_id', 'code', 'machine_id', 'route_id', 'vehicle_warehouse_id',
        'performed_by', 'status', 'offline_local_id', 'was_offline',
        'notes', 'started_at', 'completed_at', 'loaded_at',
        'latitude', 'longitude', 'geolocation_accuracy',
        // Efectivo cargado a la máquina
        'bill_100000', 'bill_50000', 'bill_20000', 'bill_10000',
        'bill_5000', 'bill_2000', 'bill_1000',
        'coin_1000', 'coin_500', 'coin_200', 'coin_100', 'coin_50',
        'total_cash_bills', 'total_cash_coins', 'total_cash',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
            'loaded_at'    => 'datetime',
            'was_offline'  => 'boolean',
            'latitude'     => 'decimal:8',
            'longitude'    => 'decimal:8',
            'geolocation_accuracy' => 'decimal:2',
            'bill_100000' => 'integer', 'bill_50000' => 'integer', 'bill_20000' => 'integer',
            'bill_10000'  => 'integer', 'bill_5000'  => 'integer', 'bill_2000'  => 'integer',
            'bill_1000'   => 'integer',
            'coin_1000'   => 'integer', 'coin_500'   => 'integer', 'coin_200'   => 'integer',
            'coin_100'    => 'integer', 'coin_50'    => 'integer',
            'total_cash_bills' => 'integer',
            'total_cash_coins' => 'integer',
            'total_cash'       => 'integer',
        ];
    }

    public function isPendingLoad(): bool
    {
        return $this->status === self::STATUS_PENDIENTE_CARGA;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_EN_SURTIDO;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETADO;
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicleWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'vehicle_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MachineStockingItem::class, 'stocking_record_id');
    }
}
