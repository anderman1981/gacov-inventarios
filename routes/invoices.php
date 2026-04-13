<?php

declare(strict_types=1);

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo de facturas.
 * Requiere autenticación, contexto de tenant Y módulo de facturas habilitado.
 */
Route::middleware(['auth', 'tenant', 'module:invoices'])
    ->prefix('invoices')
    ->name('invoices.')
    ->group(function (): void {

        // Lista y búsqueda
        Route::get('/', [InvoiceController::class, 'index'])->name('index');

        // Crear
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');

        // Ver, editar, actualizar
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');

        // Acciones de estado
        Route::post('/{invoice}/issue', [InvoiceController::class, 'issue'])->name('issue');
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');

        // Pagos
        Route::post('/{invoice}/payments', [InvoiceController::class, 'registerPayment'])->name('payments.store');

        // PDF
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');

    });

/**
 * API routes para facturas.
 */
Route::middleware(['auth', 'tenant', 'module:invoices', 'api'])
    ->prefix('api/invoices')
    ->name('api.invoices.')
    ->group(function (): void {
        Route::get('/', [InvoiceController::class, 'apiIndex'])->name('index');
        Route::get('/{invoice}', [InvoiceController::class, 'apiShow'])->name('show');
    });
