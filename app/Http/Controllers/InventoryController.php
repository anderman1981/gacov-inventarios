<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Application\UseCase\Inventory\ImportInitialInventoryHandler;
use App\Exports\InitialInventoryTemplateExport;
use App\Http\Requests\ImportInitialInventoryRequest;
use App\Models\ExcelImport;
use App\Http\Requests\AdjustStockRequest;
use App\Models\Machine;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Route;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $totalSkus  = 0;
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
                        $nestedQuery->where('products.name', 'like', "%{$search}%")
                            ->orWhere('products.code', 'like', "%{$search}%");
                    });
                })
                ->when($category !== '', fn ($query) => $query->where('products.category', $category));

            $stocks = (clone $baseQuery)
                ->orderBy('products.name')
                ->paginate($perPage)
                ->withQueryString();

            $visibleCollection = $stocks->getCollection();

            $totalSkus  = (clone $baseQuery)->where('stock.quantity', '>', 0)->count();
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
            'snack'            => 'Snacks',
            'bebida_fria'      => 'Bebidas frías',
            'bebida_caliente'  => 'Bebidas calientes',
            'insumo'           => 'Insumos',
            'otro'             => 'Otros',
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

    public function adjust(Request $request): View
    {
        abort_unless(auth()->user()?->can('inventory.adjust'), 403);

        $mainWarehouse = Warehouse::where('type', 'bodega')->firstOrFail();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        $selectedProduct = null;
        $currentStock    = 0;

        if ($productId = $request->integer('product_id')) {
            $selectedProduct = Product::find($productId);
            $currentStock = (int) (Stock::where('warehouse_id', $mainWarehouse->id)
                ->where('product_id', $productId)
                ->value('quantity') ?? 0);
        }

        return view('inventory.adjust', compact(
            'mainWarehouse', 'products', 'selectedProduct', 'currentStock'
        ));
    }

    public function storeAdjust(AdjustStockRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('inventory.adjust'), 403);

        $warehouse  = Warehouse::findOrFail($request->integer('warehouse_id'));
        $product    = Product::findOrFail($request->integer('product_id'));
        $newQty     = $request->integer('new_quantity');

        DB::transaction(function () use ($warehouse, $product, $newQty, $request): void {
            $stock = Stock::firstOrNew([
                'warehouse_id' => $warehouse->id,
                'product_id'   => $product->id,
            ]);

            $oldQty = $stock->quantity ?? 0;
            $diff   = $newQty - $oldQty;

            $stock->quantity = $newQty;
            $stock->save();

            if ($diff !== 0) {
                StockMovement::create([
                    'product_id'               => $product->id,
                    'origin_warehouse_id'      => $diff < 0 ? $warehouse->id : null,
                    'destination_warehouse_id' => $diff >= 0 ? $warehouse->id : null,
                    'movement_type'            => 'ajuste_manual',
                    'quantity'                 => abs($diff),
                    'reference_code'           => 'AJUSTE-' . now()->format('YmdHis'),
                    'notes'                    => $request->input('reason'),
                    'performed_by'             => auth()->id(),
                ]);
            }
        });

        return redirect()->route('inventory.warehouse')
            ->with('success', "Stock de «{$product->name}» ajustado correctamente.");
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
            $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%"));
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
            'carga_inicial'    => 'Carga inicial',
            'ajuste_manual'    => 'Ajuste manual',
            'traslado_salida'  => 'Traslado salida',
            'traslado_entrada' => 'Traslado entrada',
            'surtido_maquina'  => 'Surtido máquina',
            'venta_maquina'    => 'Venta máquina',
            'conteo_fisico'    => 'Conteo físico',
            'exportado_wo'     => 'Exportado WO',
        ];

        return view('inventory.movements', compact('movements', 'movementTypes', 'perPage', 'perPageOptions'));
    }

    public function vehicleStocks(Request $request): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $perPage = $this->resolvePerPage($request, 12);
        $perPageOptions = [6, 12, 24, 48];
        $search = trim((string) $request->input('search'));

        $routeQuery = Route::query()
            ->where('is_active', true);

        if ($search !== '') {
            $routeQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $routeIdsSubquery = (clone $routeQuery)->select('routes.id');

        $routes = $routeQuery
            ->with(['driver'])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Route $route): Route {
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

        return view('inventory.vehicle-stocks', compact(
            'routes',
            'totalRoutes',
            'configuredVehicles',
            'totalUnits',
            'visibleUnits',
            'perPage',
            'perPageOptions',
        ));
    }

    public function machineStocks(Request $request): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $perPage = $this->resolvePerPage($request, 12);
        $perPageOptions = [6, 12, 24, 48];
        $machineQuery = Machine::with('route')
            ->where('is_active', true);

        if ($search = trim((string) $request->input('search'))) {
            $machineQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
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
            ->through(function (Machine $machine): Machine {
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
                $machine->has_initial_inventory = $machine->machine_warehouse !== null;

                return $machine;
            });

        $routes = Route::where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalMachines = (clone $machineQuery)->count();
        $configuredWarehouses = Warehouse::query()
            ->where('type', 'maquina')
            ->whereIn('machine_id', $machineIdsSubquery)
            ->count();
        $totalUnits = (int) Stock::query()
            ->join('warehouses', 'warehouses.id', '=', 'stock.warehouse_id')
            ->where('warehouses.type', 'maquina')
            ->whereIn('warehouses.machine_id', $machineIdsSubquery)
            ->sum('stock.quantity');
        $visibleUnits = (int) $machines->getCollection()->sum('stock_units');

        return view('inventory.machine-stocks', compact(
            'machines',
            'routes',
            'totalMachines',
            'configuredWarehouses',
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
            new InitialInventoryTemplateExport(),
            'template-carga-inicial-inventario.xlsx'
        );
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
