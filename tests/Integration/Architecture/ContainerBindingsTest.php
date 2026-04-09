<?php

declare(strict_types=1);

namespace Tests\Integration\Architecture;

use App\Application\Query\Dashboard\GetDashboardOverview;
use App\Contract\Repository\ProductRepositoryInterface;
use App\Contract\Repository\TransferOrderRepositoryInterface;
use App\Domain\Shared\CompanyProfile;
use App\Infrastructure\Persistence\Eloquent\ProductRepository;
use App\Infrastructure\Persistence\Eloquent\TransferOrderRepository;
use Tests\TestCase;

final class ContainerBindingsTest extends TestCase
{
    public function test_application_services_resolve_from_container(): void
    {
        $this->assertInstanceOf(ProductRepository::class, $this->app->make(ProductRepositoryInterface::class));
        $this->assertInstanceOf(TransferOrderRepository::class, $this->app->make(TransferOrderRepositoryInterface::class));
        $this->assertInstanceOf(GetDashboardOverview::class, $this->app->make(GetDashboardOverview::class));
        $this->assertInstanceOf(CompanyProfile::class, $this->app->make(CompanyProfile::class));
    }

    public function test_required_views_exist_for_structure_baseline(): void
    {
        $this->assertTrue(view()->exists('layouts.guest'));
        $this->assertTrue(view()->exists('profile.edit'));
        $this->assertTrue(view()->exists('inventory.vehicle-stocks'));
    }
}
