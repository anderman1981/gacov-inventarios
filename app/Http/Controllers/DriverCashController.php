<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\DriverCashDelivery;
use App\Models\MachineStockingRecord;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Cash\CashInventoryCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Gestión de entregas de efectivo (billetes y monedas) a conductores.
 */
final class DriverCashController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        abort_unless(auth()->user()?->can('cash.manage'), 403);

        $cashProducts = app(CashInventoryCatalog::class)
            ->syncProducts()
            ->sortByDesc('unit_price')
            ->values();

        $defaultRouteId = $request->user()?->route_id;

        if (! $request->filled('route_id') && $defaultRouteId) {
            return redirect()->route('inventory.cash.index', array_merge($request->query(), [
                'route_id' => $defaultRouteId,
            ]));
        }

        $routeFilter = $request->filled('route_id')
            ? $request->integer('route_id')
            : $defaultRouteId;
        $driverFilter = $request->integer('driver_id') ?: null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        $deliveriesQuery = DriverCashDelivery::query()
            ->with(['route', 'driver', 'deliveredBy'])
            ->when($routeFilter, fn ($q) => $q->where('route_id', $routeFilter))
            ->when($driverFilter, fn ($q) => $q->where('driver_user_id', $driverFilter))
            ->whereBetween('delivery_date', [$dateFrom, $dateTo]);

        $deliveries = (clone $deliveriesQuery)
            ->latest('delivery_date')
            ->paginate(20)
            ->withQueryString();

        $deliveryTotals = (clone $deliveriesQuery)
            ->selectRaw('COUNT(*) as records_count, COALESCE(SUM(total_amount), 0) as total_amount, COALESCE(SUM(total_bills), 0) as total_bills, COALESCE(SUM(total_coins), 0) as total_coins')
            ->first();

        $stockingCashQuery = MachineStockingRecord::query()
            ->with(['machine', 'route', 'user'])
            ->when($routeFilter, fn ($q) => $q->where('route_id', $routeFilter))
            ->when($driverFilter, fn ($q) => $q->where('performed_by', $driverFilter))
            ->where('status', MachineStockingRecord::STATUS_COMPLETADO)
            ->whereNotNull('completed_at')
            ->whereBetween(DB::raw('DATE(completed_at)'), [$dateFrom, $dateTo]);

        $stockingCashTotals = (clone $stockingCashQuery)
            ->selectRaw('COUNT(*) as records_count, COALESCE(SUM(total_cash), 0) as total_amount, COALESCE(SUM(total_cash_bills), 0) as total_bills, COALESCE(SUM(total_cash_coins), 0) as total_coins')
            ->first();

        $recentStockingCash = (clone $stockingCashQuery)
            ->latest('completed_at')
            ->limit(8)
            ->get();

        $mainWarehouse = Warehouse::query()
            ->withoutGlobalScopes()
            ->where('type', 'bodega')
            ->where('is_active', true)
            ->when(($tenantId = $this->currentTenantId()) !== null, function ($query) use ($tenantId): void {
                $query->where(function ($nestedQuery) use ($tenantId): void {
                    $nestedQuery->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            })
            ->orderByRaw('CASE WHEN tenant_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('name')
            ->first();

        $cashStocks = collect();
        $cashInventoryTotal = 0;
        $cashInventoryUnits = 0;
        $cashWarehouses = collect();

        $cashStocksByWarehouse = Stock::query()
            ->withoutGlobalScopes()
            ->with([
                'product' => fn ($query) => $query->withoutGlobalScopes(),
                'warehouse.route' => fn ($query) => $query->withoutGlobalScopes(),
                'warehouse.machine.route' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->whereIn('product_id', $cashProducts->pluck('id'))
            ->whereHas('warehouse', function ($query): void {
                $query->withoutGlobalScopes()
                    ->where('is_active', true)
                    ->when(($tenantId = $this->currentTenantId()) !== null, function ($nestedQuery) use ($tenantId): void {
                        $nestedQuery->where(function ($tenantQuery) use ($tenantId): void {
                            $tenantQuery->where('tenant_id', $tenantId)
                                ->orWhereNull('tenant_id');
                        });
                    });
            })
            ->get()
            ->groupBy('warehouse_id');

        if ($mainWarehouse instanceof Warehouse) {
            $cashStocks = ($cashStocksByWarehouse->get($mainWarehouse->id) ?? collect())->keyBy('product_id');

            $cashProducts = $cashProducts->map(function (Product $product) use ($cashStocks): Product {
                $product->inventory_quantity = (int) ($cashStocks->get($product->id)?->quantity ?? 0);
                $product->inventory_value = $product->inventory_quantity * (int) $product->unit_price;

                return $product;
            })->values();

            $cashInventoryTotal = (int) $cashProducts->sum('inventory_value');
            $cashInventoryUnits = (int) $cashProducts->sum('inventory_quantity');
        }

        $warehouseIdsWithCash = $cashStocksByWarehouse->keys()
            ->when(
                $mainWarehouse instanceof Warehouse,
                fn ($collection) => $collection->prepend($mainWarehouse->id)
            )
            ->unique()
            ->values();

        if ($warehouseIdsWithCash->isNotEmpty()) {
            $warehouses = Warehouse::query()
                ->withoutGlobalScopes()
                ->with(['route.driver', 'machine.route'])
                ->whereIn('id', $warehouseIdsWithCash)
                ->get()
                ->keyBy('id');

            $cashWarehouses = $warehouseIdsWithCash
                ->map(function (int $warehouseId) use ($warehouses, $cashStocksByWarehouse, $cashProducts): ?array {
                    $warehouse = $warehouses->get($warehouseId);

                    if (! $warehouse instanceof Warehouse) {
                        return null;
                    }

                    $stocks = ($cashStocksByWarehouse->get($warehouseId) ?? collect())->keyBy('product_id');

                    $products = $cashProducts
                        ->map(function (Product $product) use ($stocks): Product {
                            $productClone = clone $product;
                            $productClone->inventory_quantity = (int) ($stocks->get($product->id)?->quantity ?? 0);
                            $productClone->inventory_value = $productClone->inventory_quantity * (int) $productClone->unit_price;

                            return $productClone;
                        })
                        ->filter(fn (Product $product): bool => (int) $product->inventory_quantity > 0 || $warehouse->type === 'bodega')
                        ->values();

                    if ($products->isEmpty()) {
                        return null;
                    }

                    $typeLabel = match ($warehouse->type) {
                        'bodega' => 'Bodega principal',
                        'vehiculo' => 'Vehículo',
                        'maquina' => 'Máquina',
                        default => ucfirst((string) $warehouse->type),
                    };

                    $contextLabel = match ($warehouse->type) {
                        'vehiculo' => $warehouse->route?->code
                            ? trim($warehouse->route->code.' · '.($warehouse->route->name ?? ''))
                            : ($warehouse->route?->name ?? 'Sin ruta'),
                        'maquina' => $warehouse->machine?->code
                            ? trim($warehouse->machine->code.' · '.($warehouse->machine->name ?? ''))
                            : ($warehouse->machine?->name ?? 'Sin máquina'),
                        default => $warehouse->code ?? '',
                    };

                    return [
                        'warehouse' => $warehouse,
                        'type_label' => $typeLabel,
                        'context_label' => $contextLabel,
                        'products' => $products,
                        'total_units' => (int) $products->sum('inventory_quantity'),
                        'total_value' => (int) $products->sum('inventory_value'),
                    ];
                })
                ->filter()
                ->values();
        }

        $cashProductIds = $cashProducts->pluck('id');

        $recentCashMovements = StockMovement::query()
            ->with(['product', 'user', 'fromWarehouse', 'toWarehouse'])
            ->whereIn('product_id', $cashProductIds)
            ->latest('created_at')
            ->limit(8)
            ->get();

        $recentCashTransfers = TransferOrder::query()
            ->with([
                'originWarehouse',
                'destinationWarehouse',
                'requestedBy',
                'items.product',
            ])
            ->whereHas('items', fn ($query) => $query->whereIn('product_id', $cashProductIds))
            ->latest('created_at')
            ->limit(6)
            ->get();

        $routes  = Route::withoutGlobalScopes()
            ->where('is_active', true)
            ->when(($tenantId = $this->currentTenantId()) !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->orderBy('name')
            ->get();
        $drivers = User::role('conductor')->where('is_active', true)->orderBy('name')->get();

        return view('cash.index', compact(
            'deliveries',
            'routes',
            'drivers',
            'routeFilter',
            'driverFilter',
            'dateFrom',
            'dateTo',
            'deliveryTotals',
            'stockingCashTotals',
            'recentStockingCash',
            'cashProducts',
            'cashWarehouses',
            'mainWarehouse',
            'cashInventoryTotal',
            'cashInventoryUnits',
            'recentCashMovements',
            'recentCashTransfers',
        ));
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()?->can('cash.manage'), 403);

        $routes  = Route::withoutGlobalScopes()
            ->where('is_active', true)
            ->when(($tenantId = $this->currentTenantId()) !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->orderBy('name')
            ->get();
        $drivers = User::role('conductor')->where('is_active', true)->orderBy('name')->get();
        $defaultRouteId = $request->user()?->route_id;
        $defaultDriverId = $defaultRouteId ? Route::query()->whereKey($defaultRouteId)->value('driver_user_id') : null;

        return view('cash.create', compact('routes', 'drivers', 'defaultRouteId', 'defaultDriverId'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('cash.manage'), 403);

        $validated = $request->validate([
            'route_id'          => 'required|exists:routes,id',
            'driver_user_id'    => 'required|exists:users,id',
            'delivery_date'     => 'required|date|before_or_equal:' . now()->toDateString(),
            'bill_100000'       => 'required|integer|min:0|max:9999',
            'bill_50000'        => 'required|integer|min:0|max:9999',
            'bill_20000'        => 'required|integer|min:0|max:9999',
            'bill_10000'        => 'required|integer|min:0|max:9999',
            'bill_5000'         => 'required|integer|min:0|max:9999',
            'bill_2000'         => 'required|integer|min:0|max:9999',
            'bill_1000'         => 'required|integer|min:0|max:9999',
            'coin_1000'         => 'required|integer|min:0|max:9999',
            'coin_500'          => 'required|integer|min:0|max:9999',
            'coin_200'          => 'required|integer|min:0|max:9999',
            'coin_100'          => 'required|integer|min:0|max:9999',
            'coin_50'           => 'required|integer|min:0|max:9999',
            'notes'             => 'nullable|string|max:500',
        ]);

        // Validar que al menos una denominación sea > 0
        $totalUnits = collect($validated)->only(array_merge(
            array_keys(DriverCashDelivery::BILL_DENOMINATIONS),
            array_keys(DriverCashDelivery::COIN_DENOMINATIONS)
        ))->sum();

        if ($totalUnits === 0) {
            return back()->withInput()->with('error', 'Debe ingresar al menos una denominación mayor a cero.');
        }

        try {
            DB::transaction(function () use ($validated): void {
                DriverCashDelivery::create([
                    ...$validated,
                    'delivered_by_user_id' => auth()->id(),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error al guardar en la base de datos: ' . $e->getMessage());
        }

        return redirect()->route('inventory.cash.index')
            ->with('success', 'Entrega de efectivo registrada correctamente.');
    }

    private function currentTenantId(): ?int
    {
        $tenantId = auth()->user()?->tenant_id;

        return $tenantId !== null ? (int) $tenantId : null;
    }

    public function show(int $cashDeliveryId): View
    {
        abort_unless(
            auth()->user()?->can('cash.manage') || auth()->user()?->can('cash.view'),
            403
        );

        $cashDelivery = DriverCashDelivery::query()
            ->with(['route', 'driver', 'deliveredBy'])
            ->findOrFail($cashDeliveryId);

        return view('cash.show', compact('cashDelivery'));
    }
}
