<?php

declare(strict_types=1);

use App\Http\Controllers\RouteAssignmentController;
use App\Http\Controllers\RouteScheduleCalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'module:drivers'])
    ->prefix('operations/routes')
    ->name('operations.routes.')
    ->group(function (): void {
        Route::get('/', [RouteAssignmentController::class, 'index'])->name('board');
        Route::get('/create', [RouteAssignmentController::class, 'create'])->name('create');
        Route::post('/', [RouteAssignmentController::class, 'store'])->name('store');
        Route::get('/{route}/edit', [RouteAssignmentController::class, 'edit'])->name('edit');
        Route::put('/{route}', [RouteAssignmentController::class, 'update'])->name('update');
        Route::delete('/{route}', [RouteAssignmentController::class, 'destroy'])->name('destroy');
        Route::post('/reassign', [RouteAssignmentController::class, 'reassign'])->name('reassign');
        Route::get('/calendar', [RouteScheduleCalendarController::class, 'index'])->name('calendar');
        Route::post('/calendar', [RouteScheduleCalendarController::class, 'store'])->name('calendar.store');
    });
