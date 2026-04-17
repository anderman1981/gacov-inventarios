<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\WorldOfficeTemplateExport;
use App\Models\Machine;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WorldOfficeController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('reports.worldoffice'), 403);

        return view('worldoffice.index', [
            'formats' => [
                ['category' => 'bodega', 'direction' => 'load', 'label' => 'Bodega principal · Carga'],
                ['category' => 'bodega', 'direction' => 'unload', 'label' => 'Bodega principal · Descarga'],
                ['category' => 'routes', 'direction' => 'load', 'label' => 'Vehículos / rutas · Carga'],
                ['category' => 'routes', 'direction' => 'unload', 'label' => 'Vehículos / rutas · Descarga'],
                ['category' => 'machines', 'direction' => 'load', 'label' => 'Máquinas · Carga'],
                ['category' => 'machines', 'direction' => 'unload', 'label' => 'Máquinas · Descarga'],
            ],
        ]);
    }

    public function download(Request $request, string $category, string $direction): BinaryFileResponse
    {
        abort_unless($request->user()?->can('reports.worldoffice'), 403);

        $format = $this->formatDefinition($category, $direction);

        return Excel::download(
            new WorldOfficeTemplateExport(
                sheetTitle: $format['sheet_title'],
                headings: $format['headings'],
                rows: $format['rows'],
            ),
            $format['filename'],
        );
    }

    /**
     * @return array{
     *     filename: string,
     *     sheet_title: string,
     *     headings: list<string>,
     *     rows: list<array<int, string|int|float|null>>
     * }
     */
    private function formatDefinition(string $category, string $direction): array
    {
        return match ($category.'.'.$direction) {
            'bodega.load' => [
                'filename' => 'worldoffice-bodega-carga.xlsx',
                'sheet_title' => 'BodegaCarga',
                'headings' => ['codigo_producto', 'nombre_producto', 'unidad', 'stock_actual', 'cantidad_carga', 'observaciones'],
                'rows' => $this->mainWarehouseRows('carga'),
            ],
            'bodega.unload' => [
                'filename' => 'worldoffice-bodega-descarga.xlsx',
                'sheet_title' => 'BodegaDescarga',
                'headings' => ['codigo_producto', 'nombre_producto', 'unidad', 'stock_actual', 'cantidad_descarga', 'observaciones'],
                'rows' => $this->mainWarehouseRows('descarga'),
            ],
            'routes.load' => [
                'filename' => 'worldoffice-vehiculos-carga.xlsx',
                'sheet_title' => 'VehiculosCarga',
                'headings' => ['codigo_ruta', 'nombre_ruta', 'placa', 'codigo_producto', 'nombre_producto', 'unidad', 'stock_actual', 'cantidad_carga', 'observaciones'],
                'rows' => $this->routeWarehouseRows('carga'),
            ],
            'routes.unload' => [
                'filename' => 'worldoffice-vehiculos-descarga.xlsx',
                'sheet_title' => 'VehiculosDescarga',
                'headings' => ['codigo_ruta', 'nombre_ruta', 'placa', 'codigo_producto', 'nombre_producto', 'unidad', 'stock_actual', 'cantidad_descarga', 'observaciones'],
                'rows' => $this->routeWarehouseRows('descarga'),
            ],
            'machines.load' => [
                'filename' => 'worldoffice-maquinas-carga.xlsx',
                'sheet_title' => 'MaquinasCarga',
                'headings' => ['codigo_maquina', 'nombre_maquina', 'ubicacion', 'codigo_ruta', 'codigo_producto', 'nombre_producto', 'unidad', 'stock_actual', 'cantidad_carga', 'observaciones'],
                'rows' => $this->machineWarehouseRows('carga'),
            ],
            'machines.unload' => [
                'filename' => 'worldoffice-maquinas-descarga.xlsx',
                'sheet_title' => 'MaquinasDescarga',
                'headings' => ['codigo_maquina', 'nombre_maquina', 'ubicacion', 'codigo_ruta', 'codigo_producto', 'nombre_producto', 'unidad', 'stock_actual', 'cantidad_descarga', 'observaciones'],
                'rows' => $this->machineWarehouseRows('descarga'),
            ],
            default => abort(404),
        };
    }

    /**
     * @return list<array<int, string|int|float|null>>
     */
    private function mainWarehouseRows(string $direction): array
    {
        $warehouse = Warehouse::query()
            ->where('type', 'bodega')
            ->where('is_active', true)
            ->with(['stocks.product'])
            ->first();

        $rows = [];

        if ($warehouse !== null) {
            foreach ($warehouse->stocks as $stock) {
                $rows[] = [
                    $stock->product->code ?? '',
                    $stock->product->name ?? '',
                    $stock->product->unit_of_measure ?? '',
                    (int) $stock->quantity,
                    '',
                    '',
                ];
            }
        }

        return $rows !== [] ? $rows : [[
            'PROD-001',
            'Producto de referencia',
            'Und.',
            0,
            '',
            'Ejemplo de formato',
        ]];
    }

    /**
     * @return list<array<int, string|int|float|null>>
     */
    private function routeWarehouseRows(string $direction): array
    {
        $warehouses = Warehouse::query()
            ->where('type', 'vehiculo')
            ->where('is_active', true)
            ->with(['route', 'stocks.product'])
            ->orderBy('route_id')
            ->get();

        $rows = [];

        foreach ($warehouses as $warehouse) {
            foreach ($warehouse->stocks as $stock) {
                $rows[] = [
                    $warehouse->route?->code ?? ($warehouse->route_id ? 'RT'.$warehouse->route_id : 'RT-REF'),
                    $warehouse->route?->name ?? $warehouse->name ?? 'Ruta',
                    $warehouse->route?->vehicle_plate ?? '—',
                    $stock->product->code ?? '',
                    $stock->product->name ?? '',
                    $stock->product->unit_of_measure ?? '',
                    (int) $stock->quantity,
                    '',
                    '',
                ];
            }
        }

        return $rows !== [] ? $rows : [[
            'RT-01',
            'Ruta de referencia',
            'ABC123',
            'PROD-001',
            'Producto de referencia',
            'Und.',
            0,
            '',
            'Ejemplo de formato',
        ]];
    }

    /**
     * @return list<array<int, string|int|float|null>>
     */
    private function machineWarehouseRows(string $direction): array
    {
        $machines = Machine::query()
            ->where('is_active', true)
            ->with(['route', 'warehouse.stocks.product'])
            ->orderBy('code')
            ->get();

        $rows = [];

        foreach ($machines as $machine) {
            $warehouse = $machine->warehouse;
            if ($warehouse === null) {
                continue;
            }

            foreach ($warehouse->stocks as $stock) {
                $rows[] = [
                    $machine->code ?? '',
                    $machine->name ?? '',
                    $machine->location ?? '',
                    $machine->route?->code ?? '',
                    $stock->product->code ?? '',
                    $stock->product->name ?? '',
                    $stock->product->unit_of_measure ?? '',
                    (int) $stock->quantity,
                    '',
                    '',
                ];
            }
        }

        return $rows !== [] ? $rows : [[
            'M-001',
            'Máquina de referencia',
            'Recepción',
            'RT-01',
            'PROD-001',
            'Producto de referencia',
            'Und.',
            0,
            '',
            'Ejemplo de formato',
        ]];
    }
}
