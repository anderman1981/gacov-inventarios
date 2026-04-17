<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MachineSale extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'code', 'machine_id', 'registered_by', 'status',
        'offline_local_id', 'was_offline', 'notes', 'sale_date',
        'cash_bills', 'cash_coins', 'cash_total',
    ];

    protected function casts(): array
    {
        return [
            'sale_date'   => 'date',
            'was_offline' => 'boolean',
            'cash_bills'  => 'integer',
            'cash_coins'  => 'integer',
            'cash_total'  => 'integer',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MachineSaleItem::class);
    }

    /** Total de unidades vendidas en esta venta */
    public function totalUnits(): int
    {
        return (int) $this->items->sum('quantity_sold');
    }

    /** Total de ingresos calculado desde los ítems */
    public function totalRevenue(): float
    {
        return (float) $this->items->sum(fn ($i) => $i->quantity_sold * $i->unit_price);
    }
}
