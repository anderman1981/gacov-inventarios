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
        'code', 'machine_id', 'registered_by', 'status',
        'offline_local_id', 'was_offline', 'notes', 'sale_date',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'was_offline' => 'boolean',
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
}
