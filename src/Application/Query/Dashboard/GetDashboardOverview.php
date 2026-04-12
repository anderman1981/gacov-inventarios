<?php

declare(strict_types=1);

namespace App\Application\Query\Dashboard;

use App\Contract\Repository\ProductRepositoryInterface;
use App\Contract\Repository\TransferOrderRepositoryInterface;
use App\Models\Machine;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final readonly class GetDashboardOverview
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private TransferOrderRepositoryInterface $transfers,
    ) {}

    /**
     * @return array{
     *     stats: array<string, int>,
     *     role: string,
     *     routes: Collection,
     *     recentMovements: Collection,
     *     pendingTransfers: Collection,
     *     monthMovements: Collection,
     * }
     */
    public function handle(): array
    {
        $user = Auth::user();
        $role = $user?->roles->first()?->name ?? 'guest';

        $activeRoutes = Route::query()->where('is_active', true)->count();

        $stats = [
            'total_products'  => $this->products->countActive(),
            'total_machines'  => Machine::query()->where('is_active', true)->count(),
            'movements_today' => StockMovement::query()->whereDate('created_at', today())->count(),
            'movements_month' => StockMovement::query()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'low_stock_machines' => Stock::query()
                ->where('quantity', '<', 10)
                ->whereHas('warehouse', fn ($q) => $q->where('type', 'maquina'))
                ->distinct('warehouse_id')
                ->count('warehouse_id'),
            'pending_transfers' => $this->transfers->countByStatus('pendiente'),
            'active_routes'     => $activeRoutes,
        ];

        $recentMovements = StockMovement::query()
            ->with(['product', 'user'])
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $routes = Route::query()
            ->with('driver')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(10)
            ->get();

        $pendingTransfers = TransferOrder::query()
            ->with(['originWarehouse', 'destinationWarehouse'])
            ->where('status', 'pendiente')
            ->latest()
            ->limit(5)
            ->get();

        $monthMovements = StockMovement::query()
            ->with('product')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->latest()
            ->limit(10)
            ->get();

        return compact(
            'stats',
            'role',
            'routes',
            'recentMovements',
            'pendingTransfers',
            'monthMovements',
        );
    }
}
