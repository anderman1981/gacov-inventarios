<?php

declare(strict_types=1);
use App\Http\Controllers\DriverController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/', [DriverController::class, 'dashboard'])
        ->middleware('module:drivers')
        ->name('dashboard');

    Route::middleware('module:drivers')->group(function (): void {
        Route::get('/stocking/create', [DriverController::class, 'stocking'])->name('stocking.create');
        Route::post('/stocking', [DriverController::class, 'storeStocking'])->name('stocking.store');
    });

    Route::middleware('module:sales')->group(function (): void {
        Route::get('/sales/create', [DriverController::class, 'sales'])->name('sales.create');
        Route::post('/sales', [DriverController::class, 'storeSale'])->name('sales.store');
    });

    Route::get('/inventory', [DriverController::class, 'vehicleInventory'])
        ->middleware('module:inventory')
        ->name('inventory');
});
