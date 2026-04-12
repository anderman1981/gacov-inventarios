<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SubscriptionPlan extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'phase',
        'description',
        'monthly_price',
        'yearly_price',
        'max_users',
        'max_machines',
        'max_routes',
        'max_warehouses',
        'features',
        'modules',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'features' => 'array',
        'modules' => 'array',
        'is_active' => 'boolean',
        'phase' => 'integer',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function hasModule(string $key): bool
    {
        return in_array($key, $this->modules ?? [], true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByPhase($query, int $phase)
    {
        return $query->where('phase', $phase);
    }
}
