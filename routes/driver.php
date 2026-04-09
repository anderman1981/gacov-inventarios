<?php
declare(strict_types=1);
use App\Http\Controllers\DriverController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/',                    [DriverController::class, 'dashboard'])->name('dashboard');
    Route::get('/stocking/create',     [DriverController::class, 'stocking'])->name('stocking.create');
    Route::post('/stocking',           [DriverController::class, 'storeStocking'])->name('stocking.store');
    Route::get('/sales/create',        [DriverController::class, 'sales'])->name('sales.create');
    Route::post('/sales',              [DriverController::class, 'storeSale'])->name('sales.store');
    Route::get('/inventory',           [DriverController::class, 'vehicleInventory'])->name('inventory');
});
