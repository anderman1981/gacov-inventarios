<?php

declare(strict_types=1);

namespace App\Application\Query\Dashboard;

use App\Contract\Repository\ProductRepositoryInterface;
use App\Contract\Repository\TransferOrderRepositoryInterface;
use App\Models\Machine;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

final readonly class GetDashboardOverview
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private TransferOrderRepositoryInterface $transfers,
    ) {
    }

    /**
     * @return array{stats: array<string, int>, routes: Collection<int, Route>, recentMovements: Collection<int, StockMovement>}
     */
    public function handle(): array
    {
        $stats = [
            'total_products' => $this->products->countActive(),
            'total_machines' => Machine::query()->where('is_active', true)->count(),
            'movements_today' => StockMovement::query()->whereDate('created_at', today())->count(),
            'low_stock_machines' => Stock::query()
                ->where('quantity', '<', 10)
                ->whereHas('warehouse', fn ($query) => $query->where('type', 'maquina'))
                ->distinct('warehouse_id')
                ->count('warehouse_id'),
            'pending_transfers' => $this->transfers->countByStatus('pendiente'),
        ];

        $routes = Route::query()
            ->with('driver')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recentMovements = StockMovement::query()
            ->with(['product', 'user'])
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'stats' => $stats,
            'routes' => $routes,
            'recentMovements' => $recentMovements,
        ];
    }
}
