<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCase\Inventory\ImportInitialInventoryHandler;
use App\Application\UseCase\Inventory\ImportMachineInitialInventoryHandler;
use App\Application\UseCase\Inventory\ImportVehicleInventoryHandler;
use App\Exports\InitialInventoryTemplateExport;
use App\Exports\MachineInitialInventoryTemplateExport;
use App\Exports\VehicleInventoryTemplateExport;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\ImportInitialInventoryRequest;
use App\Models\ExcelImport;
use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\InventoryAdjustmentNotification;
use App\Support\Inventory\InventoryAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\SearchHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class InventoryController extends Controller
{
    public function warehouse(Request $request): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $mainWarehouse = Warehouse::where('type', 'bodega')->first();

        $stocks = null;
        $totalUnits = 0;
        $totalSkus = 0;
        $visibleUnits = 0;
        $visibleLowStockCount = 0;
        $perPage = $this->resolvePerPage($request, 25);
        $perPageOptions = [10, 25, 50, 100];
        $search = trim((string) $request->input('search'));
        $category = trim((string) $request->input('category'));

        if ($mainWarehouse) {
            $baseQuery = Stock::query()
                ->select('stock.*')
                ->with('product')
                ->join('products', 'products.id', '=', 'stock.product_id')
                ->where('stock.warehouse_id', $mainWarehouse->id)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($nestedQuery) use ($search): void {
                        $nestedQuery->where('products.name', 'like', "%" . SearchHelper::escapeLike($search) . "%")
                            ->orWhere('products.code', 'like', "%" . SearchHelper::escapeLike($search) . "%");
                    });
                })
                ->when($category !== '', fn ($query) => $query->where('products.category', $category));

            $stocks = (clone $baseQuery)
                ->orderBy('products.name')
                ->paginate($perPage)
                ->withQueryString();

            $visibleCollection = $stocks->getCollection();

            $totalSkus = (clone $baseQuery)->where('stock.quantity', '>', 0)->count();
            $totalUnits = (int) (clone $baseQuery)->sum('stock.quantity');
            $visibleUnits = (int) $visibleCollection->sum('quantity');
            $visibleLowStockCount = $visibleCollection
                ->filter(function (Stock $stock): bool {
                    $threshold = max(1, (int) ($stock->min_quantity ?? $stock->product->min_stock_alert ?? 10));

                    return (int) $stock->quantity <= $threshold;
                })
                ->count();
        }

        $categories = [
            'snack' => 'Snacks',
            'bebida_fria' => 'Bebidas frías',
            'bebida_caliente' => 'Bebidas calientes',
            'insumo' => 'Insumos',
            'otro' => 'Otros',
        ];

        return view('inventory.warehouse', compact(
            'mainWarehouse',
            'stocks',
            'totalSkus',
            'totalUnits',
            'visibleUnits',
            'visibleLowStockCount',
            'categories',
            'perPage',
            'perPageOptions',
        ));
    }

    public function adjust(Request $request, InventoryAdjustmentService $adjustmentService): View
    {
        abort_unless(auth()->user()?->can('inventory.adjust'), 403);

        $warehouse = $adjustmentService->resolveWarehouse(
            $request->filled('warehouse_id') ? $request->integer('warehouse_id') : null
        );
        $adjustmentContext = $adjustmentService->contextFor($warehouse);
        $products = Product::where('is_active', true)->orderBy('name')->get();

        $selectedProduct = null;
        $currentStock = 0;

        if ($productId = $request->integer('product_id')) {
            $selectedProduct = Product::find($productId);
            $currentStock = (int) (Stock::where('warehouse_id', $warehouse->id)
                ->where('product_id', $productId)
                ->value('quantity') ?? 0);
        }

        return view('inventory.adjust', compact(
            'warehouse', 'adjustmentContext', 'products', 'selectedProduct', 'currentStock'
        ));
    }

    public function storeAdjust(AdjustStockRequest $request, InventoryAdjustmentService $adjustmentService): RedirectResponse
    {
        abort_unless(auth()->user()?->can('inventory.adjust'), 403);

        $warehouse = $adjustmentService->resolveWarehouse($request->integer('warehouse_id'));
        $adjustmentContext = $adjustmentService->contextFor($warehouse);
        $product = Product::findOrFail($request->integer('product_id'));
        $newQty = $request->integer('new_quantity');
        $reason = trim((string) $request->input('reason'));
        $oldQty = 0;
        $diff = 0;

        DB::transaction(function () use ($warehouse, $product, $newQty, $reason, $adjustmentContext, &$oldQty, &$diff): void {
            $stock = Stock::firstOrNew([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
            ]);

            $oldQty = (int) ($stock->quantity ?? 0);
            $diff = $newQty - $oldQty;

            $stock->quantity = $newQty;
            $stock->save();

            if ($diff !== 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'origin_warehouse_id' => $diff < 0 ? $warehouse->id : null,
                    'destination_warehouse_id' => $diff >= 0 ? $warehouse->id : null,
                    'movement_type' => $adjustmentContext['is_initial_load'] ? 'carga_inicial' : 'ajuste_manual',
                    'quantity' => abs($diff),
                    'reference_code' => 'AJUSTE-'.now()->format('YmdHis'),
                    'notes' => $reason !== '' ? $reason : null,
                    'performed_by' => auth()->id(),
                ]);
            }
        });

        $actor = auth()->user();

        if (
            $adjustmentService->shouldNotifyAdmins((bool) $adjustmentContext['requires_reason'], $actor, $diff)
            && Schema::hasTable('notifications')
            && $actor instanceof User
        ) {
            $recipients = $adjustmentService->adminRecipients($actor->id);

            if ($recipients->isNotEmpty()) {
                Notification::send(
                    $recipients,
                    new InventoryAdjustmentNotification($actor, $warehouse, $product, $oldQty, $newQty, $reason)
                );
            }
        }

        return redirect()->route($adjustmentContext['back_route'])
            ->with('success', "{$adjustmentContext['success_prefix']} para «{$product->name}».");
    }

    public function movements(Request $request): View
    {
        abort_unless(auth()->user()?->can('movements.view'), 403);

        $query = StockMovement::with(['product', 'user', 'fromWarehouse', 'toWarehouse'])
            ->orderBy('created_at', 'desc');

        if ($type = $request->input('type')) {
            $query->where('movement_type', $type);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('product', fn ($q) => $q->where('name', 'like', "%" . SearchHelper::escapeLike($search) . "%"));
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $perPage = $this->resolvePerPage($request, 25);
        $perPageOptions = [10, 25, 50, 100];
        $movements = $query->paginate($perPage)->withQueryString();

        $movementTypes = [
            'carga_inicial' => 'Carga inicial',
            'ajuste_manual' => 'Ajuste manual',
            'traslado_salida' => 'Traslado salida',
            'traslado_entrada' => 'Traslado entrada',
            'surtido_maquina' => 'Surtido máquina',
            'venta_maquina' => 'Venta máquina',
            'conteo_fisico' => 'Conteo físico',
            'exportado_wo' => 'Exportado WO',
        ];

        return view('inventory.movements', compact('movements', 'movementTypes', 'perPage', 'perPageOptions'));
    }

    public function vehicleStocks(Request $request, InventoryAdjustmentService $adjustmentService): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $perPage = $this->resolvePerPage($request, 12);
        $perPageOptions = [6, 12, 24, 48];
        $search = trim((string) $request->input('search'));

        $routeQuery = Route::query()
            ->where('is_active', true);

        if ($search !== '') {
            $routeQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%" . SearchHelper::escapeLike($search) . "%")
                    ->orWhere('code', 'like', "%" . SearchHelper::escapeLike($search) . "%");
            });
        }

        $routeIdsSubquery = (clone $routeQuery)->select('routes.id');

        $routes = $routeQuery
            ->with(['driver'])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Route $route) use ($adjustmentService): Route {
                $vehicleWarehouse = Warehouse::where('route_id', $route->id)
                    ->where('type', 'vehiculo')
                    ->first();

                $route->vehicle_warehouse = $vehicleWarehouse;
                $route->vehicle_stocks = $vehicleWarehouse
                    ? Stock::with('product')
                        ->where('warehouse_id', $vehicleWarehouse->id)
                        ->where('quantity', '>', 0)
                        ->get()
                        ->sortBy('product.name')
                    : collect();
                $route->vehicle_inventory_initialized = $vehicleWarehouse
                    ? $adjustmentService->hasInventoryActivity($vehicleWarehouse)
                    : false;

                return $route;
            });

        $totalRoutes = (clone $routeQuery)->count();
        $configuredVehicles = Warehouse::query()
            ->where('type', 'vehiculo')
            ->whereIn('route_id', $routeIdsSubquery)
            ->count();
        $totalUnits = (int) Stock::query()
            ->join('warehouses', 'warehouses.id', '=', 'stock.warehouse_id')
            ->where('warehouses.type', 'vehiculo')
            ->whereIn('warehouses.route_id', $routeIdsSubquery)
            ->sum('stock.quantity');
        $visibleUnits = (int) $routes->getCollection()
            ->sum(fn (Route $route): int => (int) $route->vehicle_stocks->sum('quantity'));
        $conductors = User::query()
            ->where('tenant_id', auth()->user()?->tenant_id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
            ->orderBy('name')
            ->get();

        return view('inventory.vehicle-stocks', compact(
            'routes',
            'totalRoutes',
            'configuredVehicles',
            'totalUnits',
            'visibleUnits',
            'perPage',
            'perPageOptions',
            'conductors',
        ));
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $tenantId = auth()->user()?->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('routes', 'code')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'vehicle_plate' => ['nullable', 'string', 'max:40'],
            'driver_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ]);

        $driverId = $validated['driver_user_id'] ?? null;

        $tenantId = auth()->user()?->tenant_id;

        $route = DB::transaction(function () use ($validated, $driverId, $tenantId): Route {
            $route = Route::query()->create([
                'tenant_id' => $tenantId,
                'name' => trim($validated['name']),
                'code' => strtoupper(trim($validated['code'])),
                'vehicle_plate' => $validated['vehicle_plate'] !== null ? strtoupper(trim((string) $validated['vehicle_plate'])) : null,
                'driver_user_id' => $driverId,
                'is_active' => true,
            ]);

            Warehouse::query()->create([
                'tenant_id' => $tenantId,
                'name' => 'Vehículo '.$route->code,
                'code' => 'VH-'.$route->code,
                'type' => 'vehiculo',
                'route_id' => $route->id,
                'responsible_user_id' => $driverId,
                'is_active' => true,
            ]);

            if ($driverId !== null) {
                User::query()->whereKey($driverId)->update(['route_id' => $route->id]);
            }

            return $route;
        });

        return redirect()
            ->route('inventory.vehicles')
            ->with('success', "Vehículo {$route->code} creado correctamente.");
    }

    public function destroyVehicle(Route $route): RedirectResponse
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);
        abort_unless($route->is_active, 404);

        DB::transaction(function () use ($route): void {
            User::query()->where('route_id', $route->id)->update(['route_id' => null]);

            Warehouse::query()
                ->where('route_id', $route->id)
                ->where('type', 'vehiculo')
                ->update([
                    'is_active' => false,
                    'responsible_user_id' => null,
                ]);

            $route->update([
                'driver_user_id' => null,
                'is_active' => false,
            ]);
        });

        return redirect()
            ->route('inventory.vehicles')
            ->with('success', "Vehículo {$route->code} eliminado correctamente.");
    }

    public function machineStocks(Request $request, InventoryAdjustmentService $adjustmentService): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $perPage = $this->resolvePerPage($request, 12);
        $perPageOptions = [6, 12, 24, 48];
        $machineQuery = Machine::with('route')
            ->where('is_active', true);

        if ($search = trim((string) $request->input('search'))) {
            $machineQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%" . SearchHelper::escapeLike($search) . "%")
                    ->orWhere('code', 'like', "%" . SearchHelper::escapeLike($search) . "%");
            });
        }

        if ($routeId = $request->integer('route_id')) {
            $machineQuery->where('route_id', $routeId);
        }

        $machineIdsSubquery = (clone $machineQuery)->select('machines.id');

        $machines = $machineQuery
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Machine $machine) use ($adjustmentService): Machine {
                $machineWarehouse = Warehouse::where('machine_id', $machine->id)
                    ->where('type', 'maquina')
                    ->first();

                $machine->machine_warehouse = $machineWarehouse;
                $machine->machine_stocks = $machineWarehouse
                    ? Stock::with('product')
                        ->where('warehouse_id', $machineWarehouse->id)
                        ->where('quantity', '>', 0)
                        ->get()
                        ->sortBy('product.name')
                        ->values()
                    : collect();

                $machine->stock_units = (int) $machine->machine_stocks->sum('quantity');
                $machine->stock_skus = $machine->machine_stocks->count();
                $machine->has_initial_inventory = $machineWarehouse
                    ? $adjustmentService->hasInventoryActivity($machineWarehouse)
                    : false;

                return $machine;
            });

        $machinesCollection = $machines->getCollection();

        $routes = Route::where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalMachines = (clone $machineQuery)->count();
        $configuredWarehouses = Warehouse::query()
            ->where('type', 'maquina')
            ->whereIn('machine_id', $machineIdsSubquery)
            ->count();
        $machinesPendingInitialLoad = (clone $machineQuery)
            ->get()
            ->filter(function (Machine $machine) use ($adjustmentService): bool {
                $machineWarehouse = Warehouse::where('machine_id', $machine->id)
                    ->where('type', 'maquina')
                    ->first();

                return $machineWarehouse instanceof Warehouse
                    && ! $adjustmentService->hasInventoryActivity($machineWarehouse);
            })
            ->count();
        $totalUnits = (int) Stock::query()
            ->join('warehouses', 'warehouses.id', '=', 'stock.warehouse_id')
            ->where('warehouses.type', 'maquina')
            ->whereIn('warehouses.machine_id', $machineIdsSubquery)
            ->sum('stock.quantity');
        $visibleUnits = (int) $machinesCollection->sum('stock_units');
        $machineBulkInitialAvailable = $machinesPendingInitialLoad > 0;

        return view('inventory.machine-stocks', compact(
            'machines',
            'routes',
            'totalMachines',
            'configuredWarehouses',
            'machinesPendingInitialLoad',
            'machineBulkInitialAvailable',
            'totalUnits',
            'visibleUnits',
            'perPage',
            'perPageOptions',
        ));
    }

    public function importForm(): View
    {
        abort_unless(auth()->user()?->can('inventory.load_excel'), 403);

        $mainWarehouse = Warehouse::query()
            ->where('type', 'bodega')
            ->where('is_active', true)
            ->first();

        $recentImports = ExcelImport::query()
            ->with('user')
            ->where('import_type', 'inventario_inicial')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('inventory.import-initial', compact('mainWarehouse', 'recentImports'));
    }

    public function downloadImportTemplate(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('inventory.load_excel'), 403);

        return Excel::download(
            new InitialInventoryTemplateExport,
            'template-carga-inicial-inventario.xlsx'
        );
    }

    public function vehicleImportForm(): View
    {
        abort_unless(auth()->user()?->can('inventory.load_vehicle_excel'), 403);

        $activeRoutes = Route::query()->where('is_active', true)->count();
        $configuredVehicles = Warehouse::query()
            ->where('type', 'vehiculo')
            ->where('is_active', true)
            ->count();
        $recentImports = ExcelImport::query()
            ->with('user')
            ->where('import_type', 'inventario_vehiculos')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('inventory.import-vehicles', compact('activeRoutes', 'configuredVehicles', 'recentImports'));
    }

    public function downloadVehicleImportTemplate(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('inventory.load_vehicle_excel'), 403);

        return Excel::download(
            new VehicleInventoryTemplateExport,
            'template-carga-masiva-vehiculos.xlsx'
        );
    }

    public function machineImportForm(InventoryAdjustmentService $adjustmentService): View
    {
        abort_unless(auth()->user()?->can('inventory.load_machine_excel'), 403);

        $activeMachines = Machine::query()->where('is_active', true)->count();
        $configuredWarehouses = Warehouse::query()
            ->where('type', 'maquina')
            ->where('is_active', true)
            ->count();
        $machinesPendingInitialLoad = Machine::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (Machine $machine) use ($adjustmentService): bool {
                $warehouse = Warehouse::query()
                    ->where('type', 'maquina')
                    ->where('machine_id', $machine->id)
                    ->first();

                return $warehouse instanceof Warehouse
                    && ! $adjustmentService->hasInventoryActivity($warehouse);
            })
            ->count();
        $recentImports = ExcelImport::query()
            ->with('user')
            ->where('import_type', 'inventario_maquinas_inicial')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('inventory.import-machines-initial', compact(
            'activeMachines',
            'configuredWarehouses',
            'machinesPendingInitialLoad',
            'recentImports',
        ));
    }

    public function downloadMachineImportTemplate(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('inventory.load_machine_excel'), 403);

        return Excel::download(
            new MachineInitialInventoryTemplateExport(),
            'template-carga-inicial-maquinas.xlsx'
        );
    }

    public function storeVehicleImport(
        ImportInitialInventoryRequest $request,
        ImportVehicleInventoryHandler $handler,
    ): RedirectResponse {
        abort_unless(auth()->user()?->can('inventory.load_vehicle_excel'), 403);

        try {
            $importLog = $handler->handle(
                file: $request->file('inventory_file'),
                user: $request->user(),
            );
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = "Carga masiva de vehículos procesada. Filas exitosas: {$importLog->processed_rows}.";

        if ($importLog->error_rows > 0) {
            $message .= " Filas con error: {$importLog->error_rows}.";
        }

        return redirect()
            ->route('inventory.vehicles.import.form')
            ->with($importLog->error_rows > 0 ? 'error' : 'success', $message);
    }

    public function storeMachineImport(
        ImportInitialInventoryRequest $request,
        ImportMachineInitialInventoryHandler $handler,
    ): RedirectResponse {
        abort_unless(auth()->user()?->can('inventory.load_machine_excel'), 403);

        try {
            $importLog = $handler->handle(
                file: $request->file('inventory_file'),
                user: $request->user(),
            );
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = "Carga inicial masiva de maquinas procesada. Filas exitosas: {$importLog->processed_rows}.";

        if ($importLog->error_rows > 0) {
            $message .= " Filas con error: {$importLog->error_rows}.";
        }

        return redirect()
            ->route('inventory.machines.import.form')
            ->with($importLog->error_rows > 0 ? 'error' : 'success', $message);
    }

    public function storeImport(
        ImportInitialInventoryRequest $request,
        ImportInitialInventoryHandler $handler,
    ): RedirectResponse {
        abort_unless(auth()->user()?->can('inventory.load_excel'), 403);

        try {
            $importLog = $handler->handle(
                file: $request->file('inventory_file'),
                user: $request->user(),
            );
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = "Importación procesada. Filas exitosas: {$importLog->processed_rows}.";

        if ($importLog->error_rows > 0) {
            $message .= " Filas con error: {$importLog->error_rows}.";
        }

        return redirect()
            ->route('inventory.import.form')
            ->with($importLog->error_rows > 0 ? 'error' : 'success', $message);
    }

    private function resolvePerPage(Request $request, int $default = 25): int
    {
        $value = $request->integer('per_page', $default);

        if (! in_array($value, [6, 10, 12, 24, 25, 48, 50, 100], true)) {
            return $default;
        }

        return $value;
    }
}
