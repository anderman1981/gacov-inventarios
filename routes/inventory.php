<?php

declare(strict_types=1);
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::middleware('module:inventory')->group(function (): void {
        Route::get('/warehouse', [InventoryController::class, 'warehouse'])->name('warehouse');
        Route::get('/vehicles', [InventoryController::class, 'vehicleStocks'])->name('vehicles');
        Route::post('/vehicles', [InventoryController::class, 'storeVehicle'])->name('vehicles.store');
        Route::delete('/vehicles/{route}', [InventoryController::class, 'destroyVehicle'])->name('vehicles.destroy');
        Route::get('/vehicles/import', [InventoryController::class, 'vehicleImportForm'])->name('vehicles.import.form');
        Route::get('/vehicles/import/template', [InventoryController::class, 'downloadVehicleImportTemplate'])->name('vehicles.import.template');
        Route::post('/vehicles/import', [InventoryController::class, 'storeVehicleImport'])->name('vehicles.import.store');
        Route::get('/machines', [InventoryController::class, 'machineStocks'])->name('machines');
        Route::get('/machines/import', [InventoryController::class, 'machineImportForm'])->name('machines.import.form');
        Route::get('/machines/import/template', [InventoryController::class, 'downloadMachineImportTemplate'])->name('machines.import.template');
        Route::post('/machines/import', [InventoryController::class, 'storeMachineImport'])->name('machines.import.store');
        Route::get('/import', [InventoryController::class, 'importForm'])->name('import.form');
        Route::get('/import/template', [InventoryController::class, 'downloadImportTemplate'])->name('import.template');
        Route::post('/import', [InventoryController::class, 'storeImport'])->name('import.store');
        Route::get('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
        Route::post('/adjust', [InventoryController::class, 'storeAdjust'])->name('adjust.store');
    });

    Route::get('/movements', [InventoryController::class, 'movements'])
        ->middleware('module:reports')
        ->name('movements');
});
