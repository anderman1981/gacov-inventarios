<?php

declare(strict_types=1);

namespace App\Application\Query\Dashboard;

use App\Models\MachineSaleItem;
use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class GetDashboardOverview
{
    public function handle(): array
    {
        $user = Auth::user();
        $role = $user?->roles->first()?->name ?? 'guest';

        $stats = [
            'total_products' => Product::where('is_active', true)->count(),
            'total_machines' => Machine::where('is_active', true)->count(),
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

        $movementTrend = $this->buildMovementTrend();
        $topSoldProducts = $this->buildTopSoldProducts();
        $salesByLocation = $this->buildSalesByLocation();
        $salesByMachine = $this->buildSalesByMachine();

        return [
            'stats' => $stats,
            'recentMovements' => $recentMovements,
            'routes' => $routes,
            'pendingTransfers' => $pendingTransfers,
            'monthMovements' => $monthMovements,
            'movementTrend' => $movementTrend,
            'topSoldProducts' => $topSoldProducts,
            'salesByLocation' => $salesByLocation,
            'salesByMachine' => $salesByMachine,
            'role' => $role,
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
        $locationLabelSql = "COALESCE(NULLIF(machines.location, ''), 'Sin ubicación')";

        return MachineSaleItem::query()
            ->join('machine_sales', 'machine_sales.id', '=', 'machine_sale_items.machine_sale_id')
            ->join('machines', 'machines.id', '=', 'machine_sales.machine_id')
            ->whereBetween('machine_sales.sale_date', [now()->subDays(29)->toDateString(), now()->toDateString()])
            ->selectRaw("{$locationLabelSql} as location_label, SUM(machine_sale_items.quantity_sold) as units, SUM(machine_sale_items.quantity_sold * machine_sale_items.unit_price) as revenue")
            ->groupBy('location_label')
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
     * @return array<int, array{label:string,units:int,revenue:float,route:string}>
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
