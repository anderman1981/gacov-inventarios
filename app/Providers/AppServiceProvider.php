<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contract\Repository\ProductRepositoryInterface;
use App\Contract\Repository\TransferOrderRepositoryInterface;
use App\Domain\Shared\CompanyProfile;
use App\Infrastructure\Persistence\Eloquent\ProductRepository;
use App\Infrastructure\Persistence\Eloquent\TransferOrderRepository;
use App\Support\Config\AmrConfig;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AmrConfig::class);

        $this->app->singleton(CompanyProfile::class, function (Application $app): CompanyProfile {
            return $app->make(AmrConfig::class)->companyProfile();
        });

        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(TransferOrderRepositoryInterface::class, TransferOrderRepository::class);
    }

    public function boot(): void
    {
    }
}
