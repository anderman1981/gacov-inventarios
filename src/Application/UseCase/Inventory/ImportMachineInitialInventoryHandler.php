<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Imports\InitialInventoryImport;
use App\Models\ExcelImport;
use App\Models\Machine;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Inventory\InventoryAdjustmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

final class ImportMachineInitialInventoryHandler
{
    public function __construct(
        private readonly InventoryAdjustmentService $adjustmentService,
    ) {}

    public function handle(UploadedFile $file, User $user): ExcelImport
    {
        $storedPath = $file->store('imports/inventario-maquinas-inicial');

        $importLog = ExcelImport::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'import_type' => 'inventario_maquinas_inicial',
            'status' => 'procesando',
            'imported_by' => $user->id,
        ]);

        try {
            $reader = new InitialInventoryImport();
            Excel::import($reader, $file);

            [$processed, $errors, $totalRows] = $this->processRows(
                rows: $reader->rows(),
                user: $user,
                referenceCode: sprintf('IMPORT-MAQ-%s-%d', now()->format('YmdHis'), $importLog->id),
            );

            $status = $processed > 0 && $errors === []
                ? 'completado'
                : ($processed > 0 ? 'completado' : 'error');

            $importLog->update([
                'status' => $status,
                'total_rows' => $totalRows,
                'processed_rows' => $processed,
                'error_rows' => count($errors),
                'error_log' => $errors === [] ? null : $errors,
            ]);
        } catch (Throwable $exception) {
            $importLog->update([
                'status' => 'error',
                'total_rows' => 0,
                'processed_rows' => 0,
                'error_rows' => 1,
                'error_log' => [$exception->getMessage()],
            ]);

            throw $exception;
        }

        return $importLog->fresh();
    }

    /**
     * @return array{0:int,1:list<string>,2:int}
     */
    private function processRows(Collection $rows, User $user, string $referenceCode): array
    {
        $processed = 0;
        $errors = [];
        $filteredRows = $rows->filter(function ($row): bool {
            $machineCode = trim((string) ($row['codigo_maquina'] ?? $row['machine_code'] ?? ''));
            $productCode = trim((string) ($row['codigo_producto'] ?? $row['codigo'] ?? ''));

            return $machineCode !== '' || $productCode !== '';
        })->values();

        $machineCodes = $filteredRows
            ->map(fn ($row): string => strtoupper(trim((string) ($row['codigo_maquina'] ?? $row['machine_code'] ?? ''))))
            ->filter()
            ->unique()
            ->values();

        /** @var Collection<string, Machine> $machines */
        $machines = Machine::query()
            ->get()
            ->keyBy(fn (Machine $machine): string => strtoupper((string) $machine->code));

        /** @var Collection<int, Warehouse> $machineWarehouses */
        $machineWarehouses = Warehouse::query()
            ->where('type', 'maquina')
            ->whereIn('machine_id', $machines->pluck('id'))
            ->get()
            ->keyBy('machine_id');

        $warehouseInitialized = $machineWarehouses->mapWithKeys(function (Warehouse $warehouse): array {
            return [$warehouse->id => $this->adjustmentService->hasInventoryActivity($warehouse)];
        });

        DB::transaction(function () use (
            $filteredRows,
            $machines,
            $machineWarehouses,
            $warehouseInitialized,
            $user,
            $referenceCode,
            &$processed,
            &$errors
        ): void {
            foreach ($filteredRows as $index => $row) {
                $rowNumber = $index + 2;
                $machineCode = strtoupper(trim((string) ($row['codigo_maquina'] ?? $row['machine_code'] ?? '')));
                $productCode = trim((string) ($row['codigo_producto'] ?? $row['codigo'] ?? ''));
                $quantity = $this->normalizeQuantity($row['cantidad_inicial'] ?? $row['cantidad'] ?? null);
                $notes = trim((string) ($row['observaciones'] ?? ''));

                if ($machineCode === '') {
                    $errors[] = "Fila {$rowNumber}: el codigo de la maquina es obligatorio.";

                    continue;
                }

                if ($productCode === '') {
                    $errors[] = "Fila {$rowNumber}: el codigo del producto es obligatorio.";

                    continue;
                }

                if ($quantity === null || $quantity < 0) {
                    $errors[] = "Fila {$rowNumber}: la cantidad inicial debe ser un numero entero mayor o igual a cero.";

                    continue;
                }

                $machine = $machines->get($machineCode);

                if (! $machine instanceof Machine) {
                    $errors[] = "Fila {$rowNumber}: no existe una maquina con codigo {$machineCode}.";

                    continue;
                }

                $machineWarehouse = $machineWarehouses->get($machine->id);

                if (! $machineWarehouse instanceof Warehouse) {
                    $errors[] = "Fila {$rowNumber}: la maquina {$machineCode} no tiene bodega configurada.";

                    continue;
                }

                if ((bool) ($warehouseInitialized->get($machineWarehouse->id) ?? false)) {
                    $errors[] = "Fila {$rowNumber}: la maquina {$machineCode} ya tuvo carga inicial. Las correcciones deben hacerse por maquina.";

                    continue;
                }

                $product = Product::query()
                    ->where('code', $productCode)
                    ->orWhere('worldoffice_code', $productCode)
                    ->first();

                if (! $product instanceof Product) {
                    $errors[] = "Fila {$rowNumber}: no existe un producto con codigo {$productCode}.";

                    continue;
                }

                $stock = Stock::query()->firstOrNew([
                    'warehouse_id' => $machineWarehouse->id,
                    'product_id' => $product->id,
                ]);

                $previousQuantity = (int) ($stock->quantity ?? 0);
                $difference = $quantity - $previousQuantity;

                $stock->quantity = $quantity;
                $stock->min_quantity = (int) $product->min_stock_alert;
                $stock->save();

                if ($difference !== 0) {
                    StockMovement::create([
                        'movement_type' => 'carga_inicial',
                        'origin_warehouse_id' => $difference < 0 ? $machineWarehouse->id : null,
                        'destination_warehouse_id' => $difference >= 0 ? $machineWarehouse->id : null,
                        'product_id' => $product->id,
                        'quantity' => abs($difference),
                        'unit_cost' => (float) $product->unit_price,
                        'reference_code' => $referenceCode,
                        'notes' => $notes !== '' ? $notes : "Carga inicial masiva de maquina {$machine->code}",
                        'performed_by' => $user->id,
                    ]);
                }

                $processed++;
            }
        });

        return [$processed, $errors, $filteredRows->count()];
    }

    private function normalizeQuantity(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = is_numeric($value)
            ? (string) $value
            : str_replace(',', '.', trim((string) $value));

        if (! is_numeric($normalized)) {
            return null;
        }

        $numericValue = (float) $normalized;
        $roundedValue = (int) round($numericValue);

        if (abs($numericValue - $roundedValue) > 0.0001) {
            return null;
        }

        return $roundedValue;
    }
}
