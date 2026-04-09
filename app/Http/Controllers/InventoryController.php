<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Application\UseCase\Inventory\ImportInitialInventoryHandler;
use App\Exports\InitialInventoryTemplateExport;
use App\Http\Requests\ImportInitialInventoryRequest;
use App\Models\ExcelImport;
use App\Http\Requests\AdjustStockRequest;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\Route;
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

        $stocks = collect();
        $totalUnits = 0;
        $totalSkus  = 0;

        if ($mainWarehouse) {
            $query = Stock::with('product')
                ->where('warehouse_id', $mainWarehouse->id);

            if ($search = $request->input('search')) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($category = $request->input('category')) {
                $query->whereHas('product', fn($q) => $q->where('category', $category));
            }

            $stocks = Stock::with('product')
                ->where('warehouse_id', $mainWarehouse->id)
                ->when($search ?? null, fn($q) => $q->whereHas('product', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                }))
                ->when($category ?? null, fn($q) => $q->whereHas('product', fn($q2) => $q2->where('category', $category)))
                ->get()
                ->sortBy('product.name');

            $totalSkus  = $stocks->where('quantity', '>', 0)->count();
            $totalUnits = (int) $stocks->sum('quantity');
        }

        $categories = [
            'snack'            => 'Snacks',
            'bebida_fria'      => 'Bebidas frías',
            'bebida_caliente'  => 'Bebidas calientes',
            'insumo'           => 'Insumos',
            'otro'             => 'Otros',
        ];

        return view('inventory.warehouse', compact(
            'mainWarehouse', 'stocks', 'totalSkus', 'totalUnits', 'categories'
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

        $movements = $query->paginate(20)->withQueryString();

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

        return view('inventory.movements', compact('movements', 'movementTypes'));
    }

    public function vehicleStocks(): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $routes = Route::with(['driver'])
            ->where('is_active', true)
            ->get()
            ->map(function (Route $route): Route {
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

        return view('inventory.vehicle-stocks', compact('routes'));
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
}
