<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ModulesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->prefix('admin')->name('admin.')->group(function () {
    // Módulos del cliente
    Route::get('modules', [ModulesController::class, 'index'])->name('modules.index');

    // Users con middleware de módulo
    Route::middleware(['module:users'])->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::get('access-profiles', [UserController::class, 'accessProfiles'])->name('users.access-profiles');
    });
});
