<?php

declare(strict_types=1);
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'module:transfers'])->prefix('transfers')->name('transfers.')->group(function () {
    Route::get('/', [TransferController::class, 'index'])->name('index');
    Route::get('/create', [TransferController::class, 'create'])->name('create');
    Route::post('/', [TransferController::class, 'store'])->name('store');
    Route::get('/{transfer}', [TransferController::class, 'show'])->name('show');
    Route::post('/{transfer}/approve', [TransferController::class, 'approve'])->name('approve');
    Route::post('/{transfer}/complete', [TransferController::class, 'complete'])->name('complete');
    Route::post('/{transfer}/cancel', [TransferController::class, 'cancel'])->name('cancel');
});
