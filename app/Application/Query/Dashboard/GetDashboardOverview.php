<?php

declare(strict_types=1);

namespace App\Application\Query\Dashboard;

use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use Illuminate\Support\Facades\Auth;

final class GetDashboardOverview
{
    public function handle(): array
    {
        $user = Auth::user();
        $role = $user?->roles->first()?->name ?? 'guest';

        $stats = [
            'total_products' => Product::count(),
            'total_machines' => Machine::count(),
            'movements_today' => StockMovement::whereDate('created_at', today())->count(),
            'movements_month' => StockMovement::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
            'low_stock_machines' => 0,
            'pending_transfers' => TransferOrder::where('status', 'pendiente')->count(),
            'active_routes' => Route::where('is_active', true)->count(),
        ];

        $recentMovements = StockMovement::with('product')
            ->whereDate('created_at', today())
            ->latest()
            ->limit(8)
            ->get();

        $routes = Route::with('driver')
            ->where('is_active', true)
            ->orderBy('code')
            ->limit(10)
            ->get();

        $pendingTransfers = TransferOrder::with(['originWarehouse', 'destinationWarehouse'])
            ->where('status', 'pendiente')
            ->latest()
            ->limit(5)
            ->get();

        $monthMovements = StockMovement::with('product')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->latest()
            ->limit(10)
            ->get();

        return compact(
            'stats',
            'recentMovements',
            'routes',
            'pendingTransfers',
            'monthMovements',
            'role'
        );
    }
}
