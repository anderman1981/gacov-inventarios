<?php

declare(strict_types=1);

use App\Http\Controllers\MachineController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'module:machines'])->group(function () {
    Route::get('machines/import', [MachineController::class, 'importForm'])->name('machines.import.form');
    Route::get('machines/import/template/routes', [MachineController::class, 'downloadRoutesTemplate'])->name('machines.import.template.routes');
    Route::post('machines/import/routes', [MachineController::class, 'storeRoutesImport'])->name('machines.import.store.routes');
    Route::get('machines/import/template/machines', [MachineController::class, 'downloadMachinesTemplate'])->name('machines.import.template.machines');
    Route::post('machines/import/machines', [MachineController::class, 'storeMachinesImport'])->name('machines.import.store.machines');
    Route::resource('machines', MachineController::class)->except(['destroy']);
    Route::post('machines/{machine}/toggle', [MachineController::class, 'toggle'])->name('machines.toggle');
});
