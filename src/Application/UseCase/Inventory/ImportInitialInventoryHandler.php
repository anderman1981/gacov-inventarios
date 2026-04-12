<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Imports\InitialInventoryImport;
use App\Models\ExcelImport;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

final class ImportInitialInventoryHandler
{
    public function handle(UploadedFile $file, User $user): ExcelImport
    {
        $warehouse = Warehouse::query()
            ->where('type', 'bodega')
            ->where('is_active', true)
            ->first();

        if (! $warehouse instanceof Warehouse) {
            throw new RuntimeException('No se encontró una bodega principal activa para realizar la carga inicial.');
        }

        $storedPath = $file->store('imports/inventario-inicial');

        $importLog = ExcelImport::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'import_type' => 'inventario_inicial',
            'status' => 'procesando',
            'imported_by' => $user->id,
        ]);

        try {
            $reader = new InitialInventoryImport;
            Excel::import($reader, $file);

            [$processed, $errors, $totalRows] = $this->processRows(
                rows: $reader->rows(),
                warehouse: $warehouse,
                user: $user,
                referenceCode: sprintf('IMPORT-%s-%d', now()->format('YmdHis'), $importLog->id),
            );

            $status = $processed > 0 && count($errors) === 0
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
     * @return array{0:int,1:array<int,string>,2:int}
     */
    private function processRows(Collection $rows, Warehouse $warehouse, User $user, string $referenceCode): array
    {
        $processed = 0;
        $errors = [];
        $filteredRows = $rows->filter(function ($row): bool {
            return trim((string) ($row['codigo_producto'] ?? $row['codigo'] ?? '')) !== '';
        })->values();

        DB::transaction(function () use ($filteredRows, $warehouse, $user, $referenceCode, &$processed, &$errors): void {
            foreach ($filteredRows as $index => $row) {
                $rowNumber = $index + 2;
                $code = trim((string) ($row['codigo_producto'] ?? $row['codigo'] ?? ''));
                $quantity = $this->normalizeQuantity($row['cantidad_inicial'] ?? $row['cantidad'] ?? null);
                $notes = trim((string) ($row['observaciones'] ?? ''));

                if ($code === '') {
                    $errors[] = "Fila {$rowNumber}: el código del producto es obligatorio.";

                    continue;
                }

                if ($quantity === null || $quantity < 0) {
                    $errors[] = "Fila {$rowNumber}: la cantidad inicial debe ser un número entero mayor o igual a cero.";

                    continue;
                }

                $product = Product::query()
                    ->where('code', $code)
                    ->orWhere('worldoffice_code', $code)
                    ->first();

                if (! $product instanceof Product) {
                    $errors[] = "Fila {$rowNumber}: no existe un producto con código {$code}.";

                    continue;
                }

                $stock = Stock::query()->firstOrNew([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                ]);

                $previousQuantity = (int) ($stock->quantity ?? 0);
                $difference = $quantity - $previousQuantity;

                $stock->quantity = $quantity;
                $stock->min_quantity = (int) $product->min_stock_alert;
                $stock->save();

                if ($difference !== 0) {
                    StockMovement::create([
                        'movement_type' => $previousQuantity === 0 && $difference > 0 ? 'carga_inicial' : 'ajuste_manual',
                        'origin_warehouse_id' => $difference < 0 ? $warehouse->id : null,
                        'destination_warehouse_id' => $difference >= 0 ? $warehouse->id : null,
                        'product_id' => $product->id,
                        'quantity' => abs($difference),
                        'unit_cost' => (float) $product->unit_price,
                        'reference_code' => $referenceCode,
                        'notes' => $notes !== '' ? $notes : "Importación inicial de inventario para {$product->code}",
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
