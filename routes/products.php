<?php

declare(strict_types=1);
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'module:products'])->group(function () {
    Route::resource('products', ProductController::class)->except(['show']);
    Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
});
