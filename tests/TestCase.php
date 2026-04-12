<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions for tests (avoids PermissionDoesNotExist errors)
        $this->seed(RoleSeeder::class);

        // Reset permission cache to ensure fresh permissions in tests
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Initialize tenant context as super_admin (null tenant = access all)
        // This prevents TenantScope from blocking queries
        $tenantContext = app(TenantContext::class);
        $tenantContext->setTenant(null); // null = super_admin, sees all data
    }

    /**
     * Create a test user with super_admin privileges (bypasses tenant middleware).
     */
    protected function createTestUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'is_super_admin' => true,
        ], $attributes));

        return $user;
    }
}
