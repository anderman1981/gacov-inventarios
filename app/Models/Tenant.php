<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Tenant extends Model
{
    use HasFactory;

    /** @var \Illuminate\Database\Eloquent\Collection<int, AppModule>|null */
    private static ?EloquentCollection $activeModuleCatalog = null;

    protected $fillable = [
        'name',
        'slug',
        'nit',
        'email',
        'phone',
        'address',
        'logo_path',
        'primary_color',
        'settings',
        'is_active',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function moduleOverrides(): HasMany
    {
        return $this->hasMany(TenantModuleOverride::class);
    }

    public function billingProfile(): HasOne
    {
        return $this->hasOne(TenantBillingProfile::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    public function currentPlan(): ?SubscriptionPlan
    {
        return $this->subscription?->plan;
    }

    public function phase(): int
    {
        $billingPhase = $this->billingProfile?->current_phase;

        if (is_int($billingPhase) && $billingPhase > 0) {
            return $billingPhase;
        }

        return $this->currentPlan()?->phase ?? 1;
    }

    public function hasModuleAccess(string $moduleKey): bool
    {
        $module = $this->activeModulesCatalog()->firstWhere('key', $moduleKey);

        if (! $module instanceof AppModule) {
            return false;
        }

        $override = $this->relationLoaded('moduleOverrides')
            ? $this->moduleOverrides->firstWhere('module_id', $module->id)
            : $this->moduleOverrides()->where('module_id', $module->id)->first();

        if ($override !== null) {
            return $override->is_enabled;
        }

        return $this->phase() >= $module->phase_required;
    }

    public function hasPhaseAccess(int $requiredPhase): bool
    {
        return $this->phase() >= $requiredPhase;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AppModule>
     */
    public function accessibleModules(): EloquentCollection
    {
        return $this->activeModulesCatalog()
            ->filter(fn (AppModule $module): bool => $this->hasModuleAccess($module->key))
            ->values();
    }

    public static function flushActiveModuleCatalog(): void
    {
        self::$activeModuleCatalog = null;
    }

    public function isOnTrial(): bool
    {
        return $this->subscription?->status === 'trial';
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
     * @return \Illuminate\Database\Eloquent\Collection<int, AppModule>
     */
    private function activeModulesCatalog(): EloquentCollection
    {
        if (self::$activeModuleCatalog instanceof EloquentCollection) {
            return self::$activeModuleCatalog;
        }

        self::$activeModuleCatalog = AppModule::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        return self::$activeModuleCatalog;
    }
}
