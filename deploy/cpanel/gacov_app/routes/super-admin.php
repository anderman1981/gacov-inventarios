<?php

declare(strict_types=1);

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\ModuleController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\ProjectCenterController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\TenantBillingController;
use App\Http\Controllers\SuperAdmin\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function (): void {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Gestión de clientes (tenants)
        Route::resource('tenants', TenantController::class)->except(['destroy']);
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');

        // Catálogo SaaS
        Route::get('plans', PlanController::class)->name('plans.index');

        // Gestión de módulos
        Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::post('modules/{module}/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');
        Route::post('modules/{module}/phase', [ModuleController::class, 'updatePhase'])->name('modules.phase');
        Route::post('modules/{module}/enable/{tenant}', [ModuleController::class, 'enableForTenant'])->name('modules.enable-tenant');
        Route::post('modules/{module}/disable/{tenant}', [ModuleController::class, 'disableForTenant'])->name('modules.disable-tenant');
        Route::delete('modules/{module}/override/{tenant}', [ModuleController::class, 'removeTenantOverride'])->name('modules.remove-override');
        Route::post('tenants/{tenant}/phase', [ModuleController::class, 'setPhaseModules'])->name('tenants.set-phase');

        Route::get('project', [ProjectCenterController::class, 'index'])->name('project.index');

        // Gestión de suscripciones
        Route::put('tenants/{tenant}/subscription', [SubscriptionController::class, 'update'])->name('tenants.subscription.update');
        Route::put('tenants/{tenant}/billing-profile', [TenantBillingController::class, 'updateProfile'])->name('tenants.billing-profile.update');
        Route::post('tenants/{tenant}/payments', [TenantBillingController::class, 'storePayment'])->name('tenants.payments.store');
        Route::get('tenants/{tenant}/billing-report', [TenantBillingController::class, 'report'])->name('tenants.billing-report');
    });
