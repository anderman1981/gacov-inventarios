<?php

declare(strict_types=1);

namespace App\Application\Query\Dashboard;

use App\Contract\Repository\ProductRepositoryInterface;
use App\Contract\Repository\TransferOrderRepositoryInterface;
use App\Models\Machine;
use App\Models\MachineSaleItem;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use Illuminate\Support\Carbon;
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
     *     movementTrend: array<int, array{label:string,value:int}>,
     *     topSoldProducts: array<int, array{label:string,code:string,units:int,revenue:float}>,
     *     salesByLocation: array<int, array{label:string,units:int,revenue:float}>,
     *     salesByMachine: array<int, array{label:string,units:int,revenue:float,route:string,location:string}>,
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

        return [
            'stats' => $stats,
            'role' => $role,
            'routes' => $routes,
            'recentMovements' => $recentMovements,
            'pendingTransfers' => $pendingTransfers,
            'monthMovements' => $monthMovements,
            'movementTrend' => $this->buildMovementTrend(),
            'topSoldProducts' => $this->buildTopSoldProducts(),
            'salesByLocation' => $this->buildSalesByLocation(),
            'salesByMachine' => $this->buildSalesByMachine(),
        ];
    }

    /**
     * @return array<int, array{label:string,value:int}>
     */
    private function buildMovementTrend(): array
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        $counts = StockMovement::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as movement_day, COUNT(*) as total')
            ->groupBy('movement_day')
            ->pluck('total', 'movement_day');

        $trend = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = Carbon::parse($startDate)->addDays($offset);
            $trend[] = [
                'label' => $date->format('d/m'),
                'value' => (int) ($counts[$date->toDateString()] ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * @return array<int, array{label:string,code:string,units:int,revenue:float}>
     */
    private function buildTopSoldProducts(): array
    {
        return MachineSaleItem::query()
            ->join('machine_sales', 'machine_sales.id', '=', 'machine_sale_items.machine_sale_id')
            ->join('products', 'products.id', '=', 'machine_sale_items.product_id')
            ->whereBetween('machine_sales.sale_date', [now()->subDays(29)->toDateString(), now()->toDateString()])
            ->selectRaw('products.id as product_id, products.code, products.name, SUM(machine_sale_items.quantity_sold) as units, SUM(machine_sale_items.quantity_sold * machine_sale_items.unit_price) as revenue')
            ->groupBy('products.id', 'products.code', 'products.name')
            ->orderByDesc('units')
            ->limit(5)
            ->get()
            ->map(static fn ($row): array => [
                'label' => (string) $row->name,
                'code' => (string) ($row->code ?? ''),
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * @return array<int, array{label:string,units:int,revenue:float}>
     */
    private function buildSalesByLocation(): array
    {
        return MachineSaleItem::query()
            ->join('machine_sales', 'machine_sales.id', '=', 'machine_sale_items.machine_sale_id')
            ->join('machines', 'machines.id', '=', 'machine_sales.machine_id')
            ->whereBetween('machine_sales.sale_date', [now()->subDays(29)->toDateString(), now()->toDateString()])
            ->selectRaw("COALESCE(NULLIF(machines.location, ''), 'Sin ubicación') as location_label, SUM(machine_sale_items.quantity_sold) as units, SUM(machine_sale_items.quantity_sold * machine_sale_items.unit_price) as revenue")
            ->groupByRaw("COALESCE(NULLIF(machines.location, ''), 'Sin ubicación')")
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(static fn ($row): array => [
                'label' => (string) $row->location_label,
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * @return array<int, array{label:string,units:int,revenue:float,route:string,location:string}>
     */
    private function buildSalesByMachine(): array
    {
        return MachineSaleItem::query()
            ->join('machine_sales', 'machine_sales.id', '=', 'machine_sale_items.machine_sale_id')
            ->join('machines', 'machines.id', '=', 'machine_sales.machine_id')
            ->leftJoin('routes', 'routes.id', '=', 'machines.route_id')
            ->whereBetween('machine_sales.sale_date', [now()->subDays(29)->toDateString(), now()->toDateString()])
            ->selectRaw("machines.id as machine_id, machines.code, machines.name, COALESCE(NULLIF(machines.location, ''), 'Sin ubicación') as location_label, COALESCE(routes.code, '') as route_code, COALESCE(routes.name, '') as route_name, SUM(machine_sale_items.quantity_sold) as units, SUM(machine_sale_items.quantity_sold * machine_sale_items.unit_price) as revenue")
            ->groupBy('machines.id', 'machines.code', 'machines.name', 'machines.location', 'routes.code', 'routes.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(static fn ($row): array => [
                'label' => trim((string) $row->code . ' — ' . (string) $row->name),
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
                'route' => trim((string) $row->route_code . ' ' . (string) $row->route_name) ?: 'Sin ruta',
                'location' => (string) $row->location_label,
            ])
            ->all();
    }
}
