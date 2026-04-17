<?php

declare(strict_types=1);
use App\Http\Controllers\DriverController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/', [DriverController::class, 'dashboard'])
        ->middleware('module:drivers')
        ->name('dashboard');

    Route::middleware('module:drivers')->group(function (): void {
        // Fase 1 — Inspección
        Route::get('/stocking/create', [DriverController::class, 'stocking'])->name('stocking.create');
        Route::post('/stocking', [DriverController::class, 'storeStocking'])->name('stocking.store');

        // Fase 2 — Carga del vehículo
        Route::get('/stocking/{record}/loading', [DriverController::class, 'stockingLoading'])->name('stocking.loading');
        Route::post('/stocking/{record}/confirm-load', [DriverController::class, 'stockingConfirmLoad'])->name('stocking.confirm-load');

        // Fase 3 — Surtido de la máquina
        Route::get('/stocking/{record}/stock', [DriverController::class, 'stockingStock'])->name('stocking.stock');
        Route::post('/stocking/{record}/complete', [DriverController::class, 'stockingComplete'])->name('stocking.complete');
    });

    Route::middleware('module:sales')->group(function (): void {
        Route::get('/sales/create', [DriverController::class, 'sales'])->name('sales.create');
        Route::post('/sales', [DriverController::class, 'storeSale'])->name('sales.store');
    });

    Route::get('/inventory', [DriverController::class, 'vehicleInventory'])
        ->middleware('module:drivers')
        ->name('inventory');
});
