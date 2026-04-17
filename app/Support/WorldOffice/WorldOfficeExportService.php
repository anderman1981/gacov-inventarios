<?php

declare(strict_types=1);

namespace App\Support\WorldOffice;

use App\Models\Machine;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

final class WorldOfficeExportService
{
    /**
     * @return array<int, array{category: string, direction: string, label: string}>
     */
    public function formats(): array
    {
        return [
            ['category' => 'bodega', 'direction' => 'load', 'label' => 'Bodega principal · Carga'],
            ['category' => 'bodega', 'direction' => 'unload', 'label' => 'Bodega principal · Descarga'],
            ['category' => 'routes', 'direction' => 'load', 'label' => 'Vehículos / rutas · Carga'],
            ['category' => 'routes', 'direction' => 'unload', 'label' => 'Vehículos / rutas · Descarga'],
            ['category' => 'machines', 'direction' => 'load', 'label' => 'Máquinas · Carga'],
            ['category' => 'machines', 'direction' => 'unload', 'label' => 'Máquinas · Descarga'],
        ];
    }

    /**
     * @return array{
     *     filename: string,
     *     sheet_title: string,
     *     headings: list<string>,
     *     rows: list<array<int, string|int|float|null>>
     * }
     */
    public function definition(string $category, string $direction): array
    {
        return match ($category.'.'.$direction) {
            'bodega.load' => [
                'filename' => 'worldoffice-bodega-carga.xlsx',
                'sheet_title' => 'BodegaCarga',
                'headings' => ['codigo_bodega', 'bodega', 'codigo_producto', 'producto', 'unidad', 'stock_actual', 'cantidad_entrada', 'referencias', 'observaciones'],
                'rows' => $this->warehouseRows(
                    warehouseType: 'bodega',
                    direction: 'load',
                    movementTypes: ['carga_inicial', 'traslado_entrada'],
                ),
            ],
            'bodega.unload' => [
                'filename' => 'worldoffice-bodega-descarga.xlsx',
                'sheet_title' => 'BodegaDescarga',
                'headings' => ['codigo_bodega', 'bodega', 'codigo_producto', 'producto', 'unidad', 'stock_actual', 'cantidad_salida', 'referencias', 'observaciones'],
                'rows' => $this->warehouseRows(
                    warehouseType: 'bodega',
                    direction: 'unload',
                    movementTypes: ['traslado_salida'],
                ),
            ],
            'routes.load' => [
                'filename' => 'worldoffice-vehiculos-carga.xlsx',
                'sheet_title' => 'VehiculosCarga',
                'headings' => ['codigo_ruta', 'ruta', 'placa', 'codigo_producto', 'producto', 'unidad', 'stock_actual', 'cantidad_entrada', 'referencias', 'observaciones'],
                'rows' => $this->routeRows(
                    direction: 'load',
                    movementTypes: ['traslado_entrada'],
                ),
            ],
            'routes.unload' => [
                'filename' => 'worldoffice-vehiculos-descarga.xlsx',
                'sheet_title' => 'VehiculosDescarga',
                'headings' => ['codigo_ruta', 'ruta', 'placa', 'codigo_producto', 'producto', 'unidad', 'stock_actual', 'cantidad_salida', 'referencias', 'observaciones'],
                'rows' => $this->routeRows(
                    direction: 'unload',
                    movementTypes: ['surtido_maquina'],
                ),
            ],
            'machines.load' => [
                'filename' => 'worldoffice-maquinas-carga.xlsx',
                'sheet_title' => 'MaquinasCarga',
                'headings' => ['codigo_maquina', 'maquina', 'ubicacion', 'codigo_ruta', 'codigo_producto', 'producto', 'unidad', 'stock_actual', 'cantidad_entrada', 'referencias', 'observaciones'],
                'rows' => $this->machineRows(
                    direction: 'load',
                    movementTypes: ['surtido_maquina'],
                ),
            ],
            'machines.unload' => [
                'filename' => 'worldoffice-maquinas-descarga.xlsx',
                'sheet_title' => 'MaquinasDescarga',
                'headings' => ['codigo_maquina', 'maquina', 'ubicacion', 'codigo_ruta', 'codigo_producto', 'producto', 'unidad', 'stock_actual', 'cantidad_salida', 'referencias', 'observaciones'],
                'rows' => $this->machineRows(
                    direction: 'unload',
                    movementTypes: ['venta_maquina'],
                ),
            ],
            default => abort(404),
        };
    }

    /**
     * @param  list<string>  $movementTypes
     * @return list<array<int, string|int|float|null>>
     */
    private function warehouseRows(string $warehouseType, string $direction, array $movementTypes): array
    {
        $warehouses = Warehouse::query()
            ->with(['stocks.product', 'route'])
            ->where('type', $warehouseType)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $this->buildRows(
            warehouses: $warehouses,
            direction: $direction,
            movementTypes: $movementTypes,
            fallbackRows: [[
                'BODEGA-01',
                'Bodega de referencia',
                'PROD-001',
                'Producto de referencia',
                'Und.',
                0,
                0,
                '—',
                'Ejemplo de formato',
            ]],
            rowBuilder: function (Warehouse $warehouse, Product $product, int $stockQuantity, array $summary): array {
                return [
                    $warehouse->code ?? '',
                    $warehouse->name ?? '',
                    $this->productCode($product),
                    $product->name ?? '',
                    $product->unit_of_measure ?? '',
                    $stockQuantity,
                    $summary['quantity'],
                    $summary['references'] ?: '—',
                    $summary['notes'] ?: '—',
                ];
            },
        );
    }

    /**
     * @param  list<string>  $movementTypes
     * @return list<array<int, string|int|float|null>>
     */
    private function routeRows(string $direction, array $movementTypes): array
    {
        $warehouses = Warehouse::query()
            ->with(['stocks.product', 'route'])
            ->where('type', 'vehiculo')
            ->where('is_active', true)
            ->orderBy('route_id')
            ->get();

        return $this->buildRows(
            warehouses: $warehouses,
            direction: $direction,
            movementTypes: $movementTypes,
            fallbackRows: [[
                'RT-01',
                'Ruta de referencia',
                'ABC123',
                'PROD-001',
                'Producto de referencia',
                'Und.',
                0,
                0,
                '—',
                'Ejemplo de formato',
            ]],
            rowBuilder: function (Warehouse $warehouse, Product $product, int $stockQuantity, array $summary): array {
                return [
                    $warehouse->route?->code ?? ($warehouse->route_id ? 'RT'.$warehouse->route_id : 'RT-REF'),
                    $warehouse->route?->name ?? $warehouse->name ?? 'Ruta',
                    $warehouse->route?->vehicle_plate ?? '—',
                    $this->productCode($product),
                    $product->name ?? '',
                    $product->unit_of_measure ?? '',
                    $stockQuantity,
                    $summary['quantity'],
                    $summary['references'] ?: '—',
                    $summary['notes'] ?: '—',
                ];
            },
        );
    }

    /**
     * @param  list<string>  $movementTypes
     * @return list<array<int, string|int|float|null>>
     */
    private function machineRows(string $direction, array $movementTypes): array
    {
        $machines = Machine::query()
            ->where('is_active', true)
            ->with(['route', 'warehouse.stocks.product'])
            ->orderBy('code')
            ->get();

        $rows = [];

        foreach ($machines as $machine) {
            $warehouse = $machine->warehouse;

            if (! $warehouse instanceof Warehouse) {
                continue;
            }

            $rows = array_merge($rows, $this->buildRows(
                warehouses: collect([$warehouse->loadMissing('stocks.product')]),
                direction: $direction,
                movementTypes: $movementTypes,
                fallbackRows: [[
                    'WO-MAQ-001',
                    'Máquina de referencia',
                    'Recepción',
                    'RT-01',
                    'PROD-001',
                    'Producto de referencia',
                    'Und.',
                    0,
                    0,
                    '—',
                    'Ejemplo de formato',
                ]],
                rowBuilder: function (Warehouse $warehouse, Product $product, int $stockQuantity, array $summary) use ($machine): array {
                    return [
                        $machine->worldoffice_code ?: $machine->code ?? '',
                        $machine->name ?? '',
                        $machine->location ?? '—',
                        $machine->route?->code ?? ($machine->route_id ? 'RT'.$machine->route_id : 'RT-REF'),
                        $this->productCode($product),
                        $product->name ?? '',
                        $product->unit_of_measure ?? '',
                        $stockQuantity,
                        $summary['quantity'],
                        $summary['references'] ?: '—',
                        $summary['notes'] ?: '—',
                    ];
                },
            ));
        }

        return $rows !== [] ? $rows : [[
            'M-001',
            'Máquina de referencia',
            '—',
            'RT-01',
            'PROD-001',
            'Producto de referencia',
            'Und.',
            0,
            0,
            '—',
            'Ejemplo de formato',
        ]];
    }

    /**
     * @param  Collection<int, Warehouse>  $warehouses
     * @param  list<string>  $movementTypes
     * @param  list<array<int, string|int|float|null>>  $fallbackRows
     * @param  callable(Warehouse, Product, int, array{quantity: int, references: string, notes: string}): array<int, string|int|float|null>  $rowBuilder
     * @return list<array<int, string|int|float|null>>
     */
    private function buildRows(Collection $warehouses, string $direction, array $movementTypes, array $fallbackRows, callable $rowBuilder): array
    {
        $summaries = $this->movementSummaries($warehouses, $direction, $movementTypes);
        $rows = [];

        foreach ($warehouses as $warehouse) {
            $stockByProductId = $warehouse->stocks->keyBy('product_id');
            $productIds = collect($stockByProductId->keys())
                ->merge($summaries->filter(fn (array $summary): bool => (int) $summary['warehouse_id'] === (int) $warehouse->id)->pluck('product_id'))
                ->unique()
                ->values();

            if ($productIds->isEmpty()) {
                continue;
            }

            $products = Product::query()
                ->whereIn('id', $productIds->all())
                ->get()
                ->keyBy('id');

            foreach ($productIds as $productId) {
                $product = $products->get((int) $productId);

                if (! $product instanceof Product) {
                    continue;
                }

                $summary = $summaries->get($warehouse->id.'-'.$product->id);

                if (! is_array($summary) || ($summary['quantity'] ?? 0) <= 0) {
                    continue;
                }

                $stock = $stockByProductId->get($product->id);

                $rows[] = $rowBuilder(
                    $warehouse,
                    $product,
                    $stock instanceof Stock ? (int) $stock->quantity : 0,
                    $summary,
                );
            }
        }

        return $rows !== [] ? $rows : $fallbackRows;
    }

    /**
     * @param  Collection<int, Warehouse>  $warehouses
     * @param  list<string>  $movementTypes
     * @return Collection<string, array{warehouse_id: int, product_id: int, quantity: int, references: string, notes: string}>
     */
    private function movementSummaries(Collection $warehouses, string $direction, array $movementTypes): Collection
    {
        if ($warehouses->isEmpty()) {
            return collect();
        }

        $warehouseIds = $warehouses->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $movements = StockMovement::query()
            ->with(['product'])
            ->whereIn('movement_type', $movementTypes)
            ->where(function ($query) use ($warehouseIds, $direction): void {
                if ($direction === 'load') {
                    $query->whereIn('destination_warehouse_id', $warehouseIds);
                } else {
                    $query->whereIn('origin_warehouse_id', $warehouseIds);
                }
            })
            ->orderBy('created_at')
            ->get();

        return $movements
            ->groupBy(fn (StockMovement $movement): string => $this->movementWarehouseId($movement, $direction).'-'.$movement->product_id)
            ->map(function (Collection $group) use ($direction): array {
                /** @var StockMovement $first */
                $first = $group->first();

                return [
                    'warehouse_id' => $this->movementWarehouseId($first, $direction),
                    'product_id' => (int) $first->product_id,
                    'quantity' => (int) $group->sum('quantity'),
                    'references' => $group->pluck('reference_code')->filter()->unique()->implode(' · '),
                    'notes' => $group->pluck('notes')->filter()->unique()->implode(' · '),
                ];
            });
    }

    private function movementWarehouseId(StockMovement $movement, string $direction): int
    {
        $warehouseId = $direction === 'load'
            ? $movement->destination_warehouse_id
            : $movement->origin_warehouse_id;

        return (int) ($warehouseId ?? 0);
    }

    private function productCode(Product $product): string
    {
        return $product->worldoffice_code ?: ($product->code ?? '');
    }
}
