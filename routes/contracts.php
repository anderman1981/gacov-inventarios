<?php

declare(strict_types=1);

use App\Http\Controllers\ContractController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])
    ->prefix('contracts')
    ->name('contracts.')
    ->group(function (): void {
        Route::get('/', [ContractController::class, 'index'])->name('index');
        Route::get('/create', [ContractController::class, 'create'])->name('create');
        Route::post('/', [ContractController::class, 'store'])->name('store');
        Route::get('/{contract}', [ContractController::class, 'show'])->name('show');
        Route::get('/{contract}/pdf', [ContractController::class, 'pdf'])->name('pdf');
        Route::post('/{contract}/resend-link', [ContractController::class, 'resendLink'])->name('resend-link');
    });

Route::middleware(['signed'])
    ->prefix('public/contracts')
    ->name('contracts.public.')
    ->group(function (): void {
        Route::get('/{tenant}/{contract}/sign', [ContractController::class, 'sign'])->name('sign');
        Route::post('/{tenant}/{contract}/sign', [ContractController::class, 'finalize'])->name('sign.store');
        Route::get('/{tenant}/{contract}/pdf', [ContractController::class, 'publicPdf'])->name('pdf');
    });
