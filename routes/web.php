<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\SubscriptionPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

require __DIR__.'/auth.php';
require __DIR__.'/driver.php';
require __DIR__.'/products.php';
require __DIR__.'/inventory.php';
require __DIR__.'/machines.php';
require __DIR__.'/transfers.php';
require __DIR__.'/admin.php';
require __DIR__.'/super-admin.php';

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('module:dashboard')
        ->name('dashboard');
    Route::get('/subscription/expired', [SubscriptionPortalController::class, 'expired'])->name('subscription.expired');
    Route::get('/subscription/upgrade', [SubscriptionPortalController::class, 'upgrade'])->name('subscription.upgrade');
    Route::get('/modules', [ModulesController::class, 'index'])->name('modules.index');
});
