<?php
declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

require __DIR__.'/auth.php';
require __DIR__.'/driver.php';
require __DIR__.'/products.php';
require __DIR__.'/inventory.php';
require __DIR__.'/machines.php';
require __DIR__.'/transfers.php';
require __DIR__.'/admin.php';

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
