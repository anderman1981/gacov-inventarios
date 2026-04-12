<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\TransferApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - GACOV Inventarios
|--------------------------------------------------------------------------
|
| RESTful API endpoints for mobile apps and external integrations.
| All routes require authentication via Sanctum.
|
*/

Route::middleware(['auth', 'tenant', 'throttle:api'])->prefix('v1')->name('api.v1.')->group(function (): void {

    // Dashboard / KPIs
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])->name('dashboard.stats');

    // Products
    Route::apiResource('products', ProductApiController::class)->only([
        'index', 'show', 'store', 'update', 'destroy',
    ]);

    // Stock (read-only via API)
    Route::get('/stock', [ProductApiController::class, 'stock'])->name('stock.index');
    Route::get('/stock/{warehouse}', [ProductApiController::class, 'stockByWarehouse'])->name('stock.warehouse');

    // Transfers
    Route::apiResource('transfers', TransferApiController::class)->only([
        'index', 'show', 'store', 'update',
    ]);
    Route::post('/transfers/{transfer}/approve', [TransferApiController::class, 'approve'])->name('transfers.approve');
    Route::post('/transfers/{transfer}/complete', [TransferApiController::class, 'complete'])->name('transfers.complete');
    Route::post('/transfers/{transfer}/cancel', [TransferApiController::class, 'cancel'])->name('transfers.cancel');
});
