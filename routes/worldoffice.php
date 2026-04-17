<?php

declare(strict_types=1);

use App\Http\Controllers\WorldOfficeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])
    ->prefix('worldoffice')
    ->name('worldoffice.')
    ->controller(WorldOfficeController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware('can:reports.worldoffice')
            ->name('index');

        Route::get('/download/{category}/{direction}', 'download')
            ->whereIn('category', ['bodega', 'routes', 'machines'])
            ->whereIn('direction', ['load', 'unload'])
            ->middleware('can:reports.worldoffice')
            ->name('download');
    });
