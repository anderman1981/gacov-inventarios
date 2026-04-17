<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Machine extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'code', 'worldoffice_code', 'name', 'location',
        'route_id', 'operator_user_id', 'type', 'is_active',
        'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function stockings(): HasMany
    {
        return $this->hasMany(MachineStockingRecord::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(MachineSale::class);
    }

    public function warehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class, 'machine_id');
    }
}
