<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Command\Driver\RegisterSale;
use App\Application\Command\Driver\RegisterStocking;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\StoreStockingRequest;
use App\Models\Machine;
use App\Models\MachineSale;
use App\Models\MachineSaleItem;
use App\Models\MachineStockingRecord;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class DriverController extends Controller
{
    public function __construct(
        private readonly RegisterStocking $registerStocking,
        private readonly RegisterSale $registerSale,
    ) {}

    public function dashboard(Request $request): View
    {
        $user = auth()->user();
        $route = $this->resolveActiveRoute($request);
        $availableRoutes = $this->availableRoutes();

        $machines = collect();
        $vehicleWarehouse = null;

        if ($route) {
            $machines = Machine::with(['warehouse.stocks.product'])
                ->where('route_id', $route->id)
                ->where('is_active', true)
                ->get()
                ->map(function (Machine $machine): Machine {
                    $machine->total_stock = $machine->warehouse
                        ? $machine->warehouse->stocks->sum('quantity')
                        : 0;

                    return $machine;
                });

            $vehicleWarehouse = Warehouse::where('route_id', $route->id)
                ->where('type', 'vehiculo')
                ->first();
        }

        $todayStockings = MachineStockingRecord::where('performed_by', $user->id)
            ->whereDate('created_at', today())
            ->count();

        $todaySales = MachineSale::where('registered_by', $user->id)
            ->whereDate('sale_date', today())
            ->count();

        // Aproximación del total vendido hoy: suma de quantity_sold * unit_price
        $todaySalesAmount = MachineSaleItem::whereHas('sale', function ($q) use ($user) {
            $q->where('registered_by', $user->id)->whereDate('sale_date', today());
        })->selectRaw('SUM(quantity_sold * unit_price) as total')->value('total') ?? 0;

        return view('driver.dashboard', compact(
            'user', 'route', 'machines', 'vehicleWarehouse',
            'todayStockings', 'todaySales', 'todaySalesAmount', 'availableRoutes'
        ));
    }

    public function stocking(Request $request): View
    {
        $route = $this->resolveActiveRoute($request);
        $availableRoutes = $this->availableRoutes();

        $machines = Machine::where('route_id', $route?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $vehicleWarehouse = Warehouse::where('route_id', $route?->id)
            ->where('type', 'vehiculo')
            ->first();

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($vehicleWarehouse): Product {
                $product->vehicle_stock = $vehicleWarehouse
                    ? (int) (Stock::where('warehouse_id', $vehicleWarehouse->id)
                        ->where('product_id', $product->id)
                        ->value('quantity') ?? 0)
                    : 0;

                return $product;
            });

        return view('driver.stocking.create', compact('machines', 'products', 'vehicleWarehouse', 'route', 'availableRoutes'));
    }

    public function storeStocking(StoreStockingRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $route = $this->resolveRouteFromInput($request->integer('route_id'));

        if (! $route instanceof Route) {
            return back()->withInput()->with('error', 'Debe seleccionar una ruta válida para registrar el surtido.');
        }

        $machine = Machine::where('id', $request->integer('machine_id'))
            ->where('route_id', $route->id)
            ->where('is_active', true)
            ->firstOrFail();

        $machineWarehouse = $machine->warehouse;
        if (! $machineWarehouse) {
            return back()->withInput()->with('error', 'La máquina no tiene bodega asignada.');
        }

        $vehicleWarehouse = Warehouse::where('route_id', $route->id)
            ->where('type', 'vehiculo')
            ->firstOrFail();

        $items = collect($request->validated('items', []))
            ->filter(fn (array $item): bool => ($item['quantity'] ?? 0) > 0);

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Debe ingresar al menos un producto con cantidad mayor a cero.');
        }

        // Extraer datos de geolocalización
        $latitude = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $longitude = $request->filled('longitude') ? (float) $request->input('longitude') : null;
        $geolocationAccuracy = $request->input('geolocation_accuracy');

        try {
            $this->registerStocking->handle(
                user: $user,
                route: $route,
                machine: $machine,
                vehicleWarehouse: $vehicleWarehouse,
                machineWarehouse: $machineWarehouse,
                items: $items,
                notes: $request->input('notes'),
                latitude: $latitude,
                longitude: $longitude,
                geolocationAccuracy: $geolocationAccuracy,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('driver.dashboard', ['route_id' => $route->id])
            ->with('success', "Surtido registrado para la máquina {$machine->name}.");
    }

    public function sales(Request $request): View
    {
        $route = $this->resolveActiveRoute($request);
        $availableRoutes = $this->availableRoutes();

        $machines = Machine::where('route_id', $route?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('driver.sales.create', compact('machines', 'products', 'route', 'availableRoutes'));
    }

    public function storeSale(StoreSaleRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $route = $this->resolveRouteFromInput($request->integer('route_id'));

        if (! $route instanceof Route) {
            return back()->withInput()->with('error', 'Debe seleccionar una ruta válida para registrar la venta.');
        }

        $machine = Machine::where('id', $request->integer('machine_id'))
            ->where('route_id', $route->id)
            ->where('is_active', true)
            ->firstOrFail();

        $machineWarehouse = $machine->warehouse;
        if (! $machineWarehouse) {
            return back()->withInput()->with('error', 'La máquina no tiene bodega asignada.');
        }

        $items = collect($request->validated('items', []))
            ->filter(fn (array $item): bool => ($item['quantity'] ?? 0) > 0);

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Debe ingresar al menos un producto con cantidad mayor a cero.');
        }

        try {
            $this->registerSale->handle(
                user: $user,
                machine: $machine,
                machineWarehouse: $machineWarehouse,
                items: $items,
                notes: $request->input('notes'),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('driver.dashboard', ['route_id' => $route->id])
            ->with('success', "Venta registrada para la máquina {$machine->name}.");
    }

    public function vehicleInventory(Request $request): View
    {
        $route = $this->resolveActiveRoute($request);
        $availableRoutes = $this->availableRoutes();

        $vehicleWarehouse = Warehouse::where('route_id', $route?->id)
            ->where('type', 'vehiculo')
            ->first();

        $stocks = collect();

        if ($vehicleWarehouse) {
            $stocks = Stock::with('product')
                ->where('warehouse_id', $vehicleWarehouse->id)
                ->where('quantity', '>', 0)
                ->get()
                ->sortBy('product.name');
        }

        return view('driver.vehicle-inventory', compact('vehicleWarehouse', 'stocks', 'route', 'availableRoutes'));
    }

    private function resolveActiveRoute(Request $request): ?Route
    {
        return $this->resolveRouteFromInput($request->integer('route_id'));
    }

    private function resolveRouteFromInput(?int $routeId): ?Route
    {
        $user = auth()->user();

        if ($routeId && $this->canSelectRoute()) {
            return Route::query()
                ->whereKey($routeId)
                ->where('is_active', true)
                ->first();
        }

        return $user?->route;
    }

    /**
     * @return Collection<int, Route>
     */
    private function availableRoutes(): Collection
    {
        if (! $this->canSelectRoute()) {
            $userRoute = auth()->user()?->route;

            return $userRoute instanceof Route
                ? collect([$userRoute])
                : collect();
        }

        return Route::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function canSelectRoute(): bool
    {
        $user = auth()->user();

        // Solo super_admin o admin/manager (roles con gestión global) pueden cambiar de ruta.
        // Los conductores solo pueden ver su propia ruta asignada.
        return (bool) ($user?->is_super_admin || $user?->hasAnyRole(['super_admin', 'admin', 'manager']));
    }
}
