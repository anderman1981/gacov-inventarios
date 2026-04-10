<?php
declare(strict_types=1);
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/warehouse',  [InventoryController::class, 'warehouse'])->name('warehouse');
    Route::get('/vehicles',   [InventoryController::class, 'vehicleStocks'])->name('vehicles');
    Route::get('/machines',   [InventoryController::class, 'machineStocks'])->name('machines');
    Route::get('/import',     [InventoryController::class, 'importForm'])->name('import.form');
    Route::get('/import/template', [InventoryController::class, 'downloadImportTemplate'])->name('import.template');
    Route::post('/import',    [InventoryController::class, 'storeImport'])->name('import.store');
    Route::get('/adjust',     [InventoryController::class, 'adjust'])->name('adjust');
    Route::post('/adjust',    [InventoryController::class, 'storeAdjust'])->name('adjust.store');
    Route::get('/movements',  [InventoryController::class, 'movements'])->name('movements');
});
