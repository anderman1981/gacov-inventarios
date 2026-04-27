<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AppModule extends Model
{
    protected static function booted(): void
    {
        static::saved(static function (): void {
            Tenant::flushActiveModuleCatalog();
        });

        static::deleted(static function (): void {
            Tenant::flushActiveModuleCatalog();
        });
    }

    protected $table = 'modules';

    protected $fillable = [
        'key',
        'name',
        'description',
        'phase_required',
        'icon',
        'color',
        'route_prefix',
        'permission_prefix',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'phase_required' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function overrides(): HasMany
    {
        return $this->hasMany(TenantModuleOverride::class, 'module_id');
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
        return $query->where('phase_required', $phase);
    }
}
