<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contract\Repository\ProductRepositoryInterface;
use App\Contract\Repository\TransferOrderRepositoryInterface;
use App\Domain\Shared\CompanyProfile;
use App\Domain\Tenant\Observers\SubscriptionObserver;
use App\Infrastructure\Persistence\Eloquent\ProductRepository;
use App\Infrastructure\Persistence\Eloquent\TransferOrderRepository;
use App\Models\Subscription;
use App\Support\Browser\ChromeDevToolsMcpClient;
use App\Support\Config\AmrConfig;
use App\Support\Documentation\ChromeDevToolsMcpRepositoryAnalyzer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AmrConfig::class);
        $this->app->singleton(ChromeDevToolsMcpClient::class);
        $this->app->singleton(ChromeDevToolsMcpRepositoryAnalyzer::class);

        $this->app->singleton(CompanyProfile::class, function (Application $app): CompanyProfile {
            return $app->make(AmrConfig::class)->companyProfile();
        });

        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(TransferOrderRepositoryInterface::class, TransferOrderRepository::class);
    }

    public function boot(): void
    {
        // Registrar observer para sincronizar fases comerciales → técnicas
        Subscription::observe(SubscriptionObserver::class);

        Gate::before(static function ($user, string $ability): ?bool {
            if (! method_exists($user, 'isSuperAdmin') || ! method_exists($user, 'hasRole')) {
                return null;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            if (! $user->hasRole('admin')) {
                return null;
            }

            $adminOperationalAbilities = [
                'dashboard.full',
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'products.import',
                'inventory.view',
                'inventory.load_excel',
                'inventory.load_vehicle_excel',
                'inventory.load_machine_excel',
                'inventory.adjust',
                'machines.view',
                'movements.view',
                'transfers.view',
                'transfers.create',
                'transfers.approve',
                'transfers.complete',
                'drivers.view',
                'drivers.assign_routes',
                'cash.manage',
                'cash.view',
                'reports.view',
                'reports.worldoffice',
                'vehicle.view',
                'vehicle.inventory.view',
            ];

            return in_array($ability, $adminOperationalAbilities, true) ? true : null;
        });
    }
}
