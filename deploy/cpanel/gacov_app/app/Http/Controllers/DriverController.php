<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Command\Driver\CompleteStockingRecord;
use App\Application\Command\Driver\CreateStockingInspection;
use App\Application\Command\Driver\RegisterSale;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\StoreStockingRequest;
use App\Models\Machine;
use App\Models\MachineSale;
use App\Models\MachineSaleItem;
use App\Models\MachineStockingRecord;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Routes\RouteScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class DriverController extends Controller
{
    public function __construct(
        private readonly CreateStockingInspection $createStockingInspection,
        private readonly CompleteStockingRecord $completeStockingRecord,
        private readonly RegisterSale $registerSale,
        private readonly RouteScheduleService $routeScheduleService,
    ) {}

    public function dashboard(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user?->isSuperAdmin() || $user?->can('dashboard.own'), 403);

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
        abort_unless(auth()->user()?->can('stockings.create'), 403);

        $route = $this->resolveActiveRoute($request);
        $availableRoutes = $this->availableRoutes();
        $productSearch = mb_strtolower(trim((string) $request->string('product_search', '')));
        $stockFilter = $request->string('stock_filter', 'all')->toString();
        $perPage = max(10, min(100, $request->integer('per_page', 10)));
        $perPageOptions = [10, 20, 50, 100];

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

        $products = $products->filter(function (Product $product) use ($productSearch, $stockFilter): bool {
            $productName = mb_strtolower(trim((string) $product->name));
            $productCode = mb_strtolower(trim((string) $product->code));
            $productCategory = mb_strtolower(trim((string) ($product->category ?? '')));

            $matchesSearch = $productSearch === ''
                || str_contains($productName, $productSearch)
                || str_contains($productCode, $productSearch)
                || str_contains($productCategory, $productSearch);

            $matchesStock = match ($stockFilter) {
                'with_stock' => $product->vehicle_stock > 0,
                'low_stock' => $product->vehicle_stock > 0 && $product->vehicle_stock <= 5,
                'empty' => $product->vehicle_stock <= 0,
                default => true,
            };

            return $matchesSearch && $matchesStock;
        })->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $products = new LengthAwarePaginator(
            $products->forPage($currentPage, $perPage)->values(),
            $products->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url()]
        );
        $products->appends($request->except('page'));

        return view('driver.stocking.create', compact(
            'machines',
            'products',
            'vehicleWarehouse',
            'route',
            'availableRoutes',
            'productSearch',
            'stockFilter',
            'perPage',
            'perPageOptions',
        ));
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
            $record = $this->createStockingInspection->handle(
                user: $user,
                route: $route,
                machine: $machine,
                vehicleWarehouse: $vehicleWarehouse,
                items: $items,
                notes: $request->input('notes'),
                latitude: $latitude,
                longitude: $longitude,
                geolocationAccuracy: $geolocationAccuracy,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('driver.stocking.loading', $record)
            ->with('success', "Inspección registrada. Ahora carga los productos en tu vehículo.");
    }

    /** Fase 2 — Vista de carga: el conductor ve qué debe sacar del vehículo. */
    public function stockingLoading(int $record): View
    {
        $user = auth()->user();
        abort_unless($user?->can('stockings.create'), 403);
        $record = $this->resolveStockingRecord($record);
        abort_unless(
            $record->performed_by === $user->id || $user->can('stockings.manage'),
            403
        );
        abort_unless($record->isPendingLoad(), 403);

        $items = $record->items()->with('product')->get();

        return view('driver.stocking.loading', compact('record', 'items'));
    }

    /** Fase 2 — POST: confirma que cargó el vehículo, avanza a en_surtido. */
    public function stockingConfirmLoad(int $record): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user?->can('stockings.create'), 403);
        $record = $this->resolveStockingRecord($record);
        abort_unless(
            $record->performed_by === $user->id || $user->can('stockings.manage'),
            403
        );
        abort_unless($record->isPendingLoad(), 403);

        $record->update([
            'status'    => MachineStockingRecord::STATUS_EN_SURTIDO,
            'loaded_at' => now(),
        ]);

        return redirect()->route('driver.stocking.stock', $record)
            ->with('success', 'Carga confirmada. Ya puedes ir a surtir la máquina.');
    }

    /** Fase 3 — Vista de surtido: el conductor registra efectivo y confirma que surtió. */
    public function stockingStock(int $record): View
    {
        $user = auth()->user();
        abort_unless($user?->can('stockings.create'), 403);
        $record = $this->resolveStockingRecord($record);
        abort_unless(
            $record->performed_by === $user->id || $user->can('stockings.manage'),
            403
        );
        abort_unless($record->isInProgress(), 403);

        $items = $record->items()->with('product')->get();

        return view('driver.stocking.stock', compact('record', 'items'));
    }

    /** Fase 3 — POST: completa el surtido, descuenta stock, notifica. */
    public function stockingComplete(int $record, Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user?->can('stockings.create'), 403);
        $record = $this->resolveStockingRecord($record);
        abort_unless(
            $record->performed_by === $user->id || $user->can('stockings.manage'),
            403
        );
        abort_unless($record->isInProgress(), 403);

        $machineWarehouse = $record->machine?->warehouse;

        if (! $machineWarehouse instanceof Warehouse) {
            return back()->with('error', 'La máquina no tiene bodega asignada. Contacta al administrador.');
        }

        $cashFields = array_merge(
            array_keys(MachineStockingRecord::BILL_DENOMINATIONS),
            array_keys(MachineStockingRecord::COIN_DENOMINATIONS)
        );

        $cashDenominations = [];
        foreach ($cashFields as $field) {
            $cashDenominations[$field] = (int) $request->input($field, 0);
        }

        try {
            $this->completeStockingRecord->handle($record, $machineWarehouse, $cashDenominations);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('driver.dashboard', ['route_id' => $record->route_id])
            ->with('success', "¡Máquina {$record->machine?->name} surtida exitosamente!");
    }

    public function sales(Request $request): View
    {
        abort_unless(auth()->user()?->can('sales.create'), 403);

        $route = $this->resolveActiveRoute($request);
        $availableRoutes = $this->availableRoutes();
        $selectedMachineId = $request->integer('machine_id');
        $stockFilter = $request->string('stock_filter', 'all')->toString();
        $perPage = max(10, min(100, $request->integer('per_page', 10)));
        $perPageOptions = [10, 20, 50, 100];

        $machines = collect();
        $selectedMachine = null;
        $machineStockByProductId = collect();
        $selectedMachineStockTotal = 0;
        $selectedMachineAvailableSkus = 0;

        if ($route instanceof Route) {
            $machines = Machine::with(['warehouse.stocks'])
                ->where('route_id', $route->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $selectedMachine = $machines->firstWhere('id', $selectedMachineId)
                ?? ($machines->count() === 1 ? $machines->first() : null);

            if ($selectedMachine?->warehouse?->stocks) {
                $machineStockByProductId = $selectedMachine->warehouse->stocks
                    ->pluck('quantity', 'product_id')
                    ->map(fn ($quantity): int => (int) $quantity);
                $selectedMachineStockTotal = (int) $machineStockByProductId->sum();
                $selectedMachineAvailableSkus = (int) $machineStockByProductId
                    ->filter(static fn (int $quantity): bool => $quantity > 0)
                    ->count();
            }
        }

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($machineStockByProductId): Product {
                $machineStock = (int) ($machineStockByProductId->get($product->id, 0));
                $product->machine_stock = $machineStock;
                $product->machine_stock_label = match (true) {
                    $machineStock <= 0 => 'Sin stock',
                    $machineStock <= 5 => 'Stock bajo',
                    default => 'Disponible',
                };
                $product->machine_stock_class = match (true) {
                    $machineStock <= 0 => 'sale-state-pill--empty',
                    $machineStock <= 5 => 'sale-state-pill--low',
                    default => 'sale-state-pill--ok',
                };
                $product->category_initials = $this->categoryInitials((string) $product->category);

                return $product;
            });

        $products = match ($stockFilter) {
            'with_stock' => $products->filter(fn (Product $product): bool => $product->machine_stock > 0)->values(),
            'low_stock' => $products->filter(fn (Product $product): bool => $product->machine_stock > 0 && $product->machine_stock <= 5)->values(),
            'empty' => $products->filter(fn (Product $product): bool => $product->machine_stock <= 0)->values(),
            default => $products->values(),
        };

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $products = new LengthAwarePaginator(
            $products->forPage($currentPage, $perPage)->values(),
            $products->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url()]
        );
        $products->appends($request->except('page'));

        return view('driver.sales.create', compact(
            'machines',
            'products',
            'route',
            'availableRoutes',
            'selectedMachine',
            'selectedMachineStockTotal',
            'selectedMachineAvailableSkus',
            'stockFilter',
            'perPage',
            'perPageOptions',
        ));
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
        abort_unless(auth()->user()?->can('vehicle.inventory.view'), 403);

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

        if (! $user instanceof User) {
            return null;
        }

        if ($routeId && $this->canManageAnyRoute($user)) {
            return Route::query()
                ->whereKey($routeId)
                ->where('is_active', true)
                ->first();
        }

        if ($this->canManageAnyRoute($user)) {
            return $user->route;
        }

        $availableRoutes = $this->routeScheduleService->availableRoutesForUser($user, today());

        if ($routeId) {
            return $availableRoutes->firstWhere('id', $routeId) ?? $availableRoutes->first();
        }

        return $availableRoutes->first();
    }

    private function resolveStockingRecord(int $recordId): MachineStockingRecord
    {
        return MachineStockingRecord::query()
            ->whereKey($recordId)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Route>
     */
    private function availableRoutes(): Collection
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return collect();
        }

        if ($this->canManageAnyRoute($user)) {
            return Route::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return $this->routeScheduleService->availableRoutesForUser($user, today());
    }

    private function canManageAnyRoute(User $user): bool
    {
        return $this->routeScheduleService->canManageAnyRoute($user);
    }

    private function categoryInitials(string $category): string
    {
        $segments = array_filter(explode('_', trim($category)));

        if ($segments === []) {
            return 'NA';
        }

        return strtoupper(implode('', array_map(
            static fn (string $segment): string => mb_substr($segment, 0, 1),
            $segments
        )));
    }
}
