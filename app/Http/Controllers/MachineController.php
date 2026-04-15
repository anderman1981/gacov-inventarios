<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCase\Machines\ImportMachinesHandler;
use App\Application\UseCase\Machines\ImportRoutesHandler;
use App\Exports\MachinesTemplateExport;
use App\Exports\RoutesTemplateExport;
use App\Http\Requests\BulkCatalogImportRequest;
use App\Http\Requests\MachineRequest;
use App\Models\ExcelImport;
use App\Models\Machine;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\SearchHelper;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class MachineController extends Controller
{
    public function importForm(): View
    {
        $this->authorizeBulkImport();

        $recentRouteImports = ExcelImport::query()
            ->with('user')
            ->where('import_type', 'rutas')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentMachineImports = ExcelImport::query()
            ->with('user')
            ->where('import_type', 'maquinas')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('machines.import', compact('recentRouteImports', 'recentMachineImports'));
    }

    public function downloadRoutesTemplate(): BinaryFileResponse
    {
        $this->authorizeBulkImport();

        return Excel::download(
            new RoutesTemplateExport,
            'template-carga-rutas.xlsx'
        );
    }

    public function storeRoutesImport(
        BulkCatalogImportRequest $request,
        ImportRoutesHandler $handler,
    ): RedirectResponse {
        $this->authorizeBulkImport();

        try {
            $importLog = $handler->handle(
                file: $request->file('import_file'),
                user: $request->user(),
            );
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = "Importación de rutas procesada. Filas exitosas: {$importLog->processed_rows}.";

        if ($importLog->error_rows > 0) {
            $message .= " Filas con error: {$importLog->error_rows}.";
        }

        return redirect()
            ->route('machines.import.form')
            ->with($importLog->error_rows > 0 ? 'error' : 'success', $message);
    }

    public function downloadMachinesTemplate(): BinaryFileResponse
    {
        $this->authorizeBulkImport();

        return Excel::download(
            new MachinesTemplateExport,
            'template-carga-maquinas.xlsx'
        );
    }

    public function storeMachinesImport(
        BulkCatalogImportRequest $request,
        ImportMachinesHandler $handler,
    ): RedirectResponse {
        $this->authorizeBulkImport();

        try {
            $importLog = $handler->handle(
                file: $request->file('import_file'),
                user: $request->user(),
            );
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = "Importación de máquinas procesada. Filas exitosas: {$importLog->processed_rows}.";

        if ($importLog->error_rows > 0) {
            $message .= " Filas con error: {$importLog->error_rows}.";
        }

        return redirect()
            ->route('machines.import.form')
            ->with($importLog->error_rows > 0 ? 'error' : 'success', $message);
    }

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('machines.view'), 403);

        $query = Machine::with(['route', 'operator']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%" . SearchHelper::escapeLike($search) . "%")
                    ->orWhere('code', 'like', "%" . SearchHelper::escapeLike($search) . "%");
            });
        }

        if ($routeId = $request->input('route_id')) {
            $query->where('route_id', $routeId);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $machines = $query->orderBy('name')->paginate(20)->withQueryString();

        // Cargar stock total de cada máquina desde su bodega tipo 'maquina'
        $machineIds = $machines->pluck('id')->toArray();
        $warehouses = Warehouse::whereIn('machine_id', $machineIds)
            ->where('type', 'maquina')
            ->get()
            ->keyBy('machine_id');

        $stockTotals = [];
        foreach ($warehouses as $machineId => $warehouse) {
            $stockTotals[$machineId] = (int) $warehouse->stocks()->sum('quantity');
        }

        $routes = Route::where('is_active', true)->orderBy('name')->get();

        return view('machines.index', compact('machines', 'routes', 'stockTotals'));
    }

    public function show(Machine $machine): View
    {
        abort_unless(auth()->user()?->can('machines.view'), 403);

        $machine->load(['route', 'operator']);

        // Bodega tipo 'maquina' de esta máquina
        $warehouse = Warehouse::where('machine_id', $machine->id)
            ->where('type', 'maquina')
            ->first();

        // Stock actual por producto
        $stockItems = $warehouse
            ? $warehouse->stocks()->with('product')->get()
            : collect();

        // Últimos 5 surtidos
        $recentStockings = $machine->stockings()
            ->with('user')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Últimas 5 ventas
        $recentSales = $machine->sales()
            ->with('user')
            ->withCount('items')
            ->selectRaw('machine_sales.*, SUM(machine_sale_items.quantity_sold) as total_units')
            ->leftJoin('machine_sale_items', 'machine_sale_items.machine_sale_id', '=', 'machine_sales.id')
            ->groupBy('machine_sales.id')
            ->orderByDesc('machine_sales.created_at')
            ->limit(5)
            ->get()
            ->each(function (MachineSale $sale): void {
                $sale->total_units = (int) ($sale->total_units ?? 0);
            });

        return view('machines.show', compact('machine', 'warehouse', 'stockItems', 'recentStockings', 'recentSales'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('machines.create'), 403);

        $routes = Route::where('is_active', true)->orderBy('name')->get();
        $operators = User::where('is_active', true)->orderBy('name')->get();

        return view('machines.create', compact('routes', 'operators'));
    }

    public function store(MachineRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('machines.create'), 403);

        $machine = Machine::create($request->validated());

        // Crear bodega tipo 'maquina' si no existe ninguna asociada
        $existingWarehouse = Warehouse::where('machine_id', $machine->id)
            ->where('type', 'maquina')
            ->exists();

        if (! $existingWarehouse) {
            Warehouse::create([
                'name' => 'Bodega '.$machine->name,
                'code' => $machine->code,
                'type' => 'maquina',
                'machine_id' => $machine->id,
                'route_id' => $machine->route_id,
                'is_active' => true,
            ]);
        }

        return redirect()->route('machines.show', $machine)
            ->with('success', 'Máquina creada correctamente.');
    }

    public function edit(Machine $machine): View
    {
        abort_unless(auth()->user()?->can('machines.edit'), 403);

        $routes = Route::where('is_active', true)->orderBy('name')->get();
        $operators = User::where('is_active', true)->orderBy('name')->get();

        return view('machines.edit', compact('machine', 'routes', 'operators'));
    }

    public function update(MachineRequest $request, Machine $machine): RedirectResponse
    {
        abort_unless(auth()->user()?->can('machines.edit'), 403);

        $machine->update($request->validated());

        return redirect()->route('machines.show', $machine)
            ->with('success', 'Máquina actualizada correctamente.');
    }

    public function toggle(Machine $machine): RedirectResponse
    {
        abort_unless(auth()->user()?->can('machines.edit'), 403);

        $machine->update(['is_active' => ! $machine->is_active]);
        $estado = $machine->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Máquina {$estado} correctamente.");
    }

    private function authorizeBulkImport(): void
    {
        abort_unless(
            auth()->user()?->can('machines.create') || auth()->user()?->can('inventory.load_excel'),
            403
        );
    }
}
