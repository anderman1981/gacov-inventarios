<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrega de efectivo (billetes y monedas colombianas) a un conductor.
 * Se registra antes de que el conductor salga a su ruta para surtir máquinas.
 */
final class DriverCashDelivery extends Model
{
    use BelongsToTenant;

    /** Denominaciones de billetes colombianos (valor x cantidad = subtotal) */
    public const BILL_DENOMINATIONS = [
        'bill_100000' => 100000,
        'bill_50000'  => 50000,
        'bill_20000'  => 20000,
        'bill_10000'  => 10000,
        'bill_5000'   => 5000,
        'bill_2000'   => 2000,
        'bill_1000'   => 1000,
    ];

    /** Denominaciones de monedas colombianas */
    public const COIN_DENOMINATIONS = [
        'coin_1000' => 1000,
        'coin_500'  => 500,
        'coin_200'  => 200,
        'coin_100'  => 100,
        'coin_50'   => 50,
    ];

    protected $fillable = [
        'tenant_id', 'route_id', 'driver_user_id', 'delivered_by_user_id', 'delivery_date',
        'bill_100000', 'bill_50000', 'bill_20000', 'bill_10000', 'bill_5000', 'bill_2000', 'bill_1000',
        'coin_1000', 'coin_500', 'coin_200', 'coin_100', 'coin_50',
        'total_bills', 'total_coins', 'total_amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'bill_100000' => 'integer', 'bill_50000' => 'integer', 'bill_20000' => 'integer',
            'bill_10000' => 'integer', 'bill_5000' => 'integer', 'bill_2000' => 'integer',
            'bill_1000' => 'integer',
            'coin_1000' => 'integer', 'coin_500' => 'integer', 'coin_200' => 'integer',
            'coin_100' => 'integer', 'coin_50' => 'integer',
            'total_bills' => 'integer', 'total_coins' => 'integer', 'total_amount' => 'integer',
        ];
    }

    /** Calcula los totales y los guarda en el modelo antes de crear/actualizar */
    public static function bootDriverCashDelivery(): void
    {
        static::saving(function (self $model): void {
            $bills = 0;
            foreach (self::BILL_DENOMINATIONS as $field => $value) {
                $bills += ($model->$field ?? 0) * $value;
            }
            $coins = 0;
            foreach (self::COIN_DENOMINATIONS as $field => $value) {
                $coins += ($model->$field ?? 0) * $value;
            }
            $model->total_bills  = $bills;
            $model->total_coins  = $coins;
            $model->total_amount = $bills + $coins;
        });
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }
}
