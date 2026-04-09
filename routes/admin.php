<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
});
