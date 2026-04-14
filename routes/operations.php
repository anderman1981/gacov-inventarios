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
        Route::post('/reassign', [RouteAssignmentController::class, 'reassign'])->name('reassign');
        Route::get('/calendar', [RouteScheduleCalendarController::class, 'index'])->name('calendar');
        Route::post('/calendar', [RouteScheduleCalendarController::class, 'store'])->name('calendar.store');
    });
