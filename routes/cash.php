<?php

declare(strict_types=1);

use App\Http\Controllers\DriverCashController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])
    ->prefix('cash')
    ->name('cash.')
    ->group(function (): void {
        Route::get('/', [DriverCashController::class, 'index'])->name('index');
        Route::get('/create', [DriverCashController::class, 'create'])->name('create');
        Route::post('/', [DriverCashController::class, 'store'])->name('store');
        Route::get('/{cashDeliveryId}', [DriverCashController::class, 'show'])->name('show');
    });
