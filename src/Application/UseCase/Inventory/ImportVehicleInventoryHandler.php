<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Imports\InitialInventoryImport;
use App\Models\ExcelImport;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\InventoryAdjustmentNotification;
use App\Support\Inventory\InventoryAdjustmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

final class ImportVehicleInventoryHandler
{
    public function __construct(
        private readonly InventoryAdjustmentService $adjustmentService,
    ) {}

    public function handle(UploadedFile $file, User $user): ExcelImport
    {
        $storedPath = $file->store('imports/inventario-vehiculos');

        $importLog = ExcelImport::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'import_type' => 'inventario_vehiculos',
            'status' => 'procesando',
            'imported_by' => $user->id,
        ]);

        try {
            $reader = new InitialInventoryImport;
            Excel::import($reader, $file);

            [$processed, $errors, $totalRows, $notifications] = $this->processRows(
                rows: $reader->rows(),
                user: $user,
                referenceCode: sprintf('IMPORT-VEH-%s-%d', now()->format('YmdHis'), $importLog->id),
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

            $this->sendNotifications($user, collect($notifications));
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
     * @return array{0:int,1:array<int,string>,2:int,3:list<array{warehouse:Warehouse,product:Product,old:int,new:int,reason:string}>}
     */
    private function processRows(Collection $rows, User $user, string $referenceCode): array
    {
        $processed = 0;
        $errors = [];
        $notificationPayloads = [];
        $filteredRows = $rows->filter(function ($row): bool {
            $routeCode = trim((string) ($row['codigo_ruta'] ?? $row['route_code'] ?? ''));
            $productCode = trim((string) ($row['codigo_producto'] ?? $row['codigo'] ?? ''));

            return $routeCode !== '' || $productCode !== '';
        })->values();

        $routeCodes = $filteredRows
            ->map(fn ($row): string => strtoupper(trim((string) ($row['codigo_ruta'] ?? $row['route_code'] ?? ''))))
            ->filter()
            ->unique()
            ->values();

        /** @var Collection<string, Route> $routes */
        $routes = Route::query()
            ->whereIn('code', $routeCodes)
            ->get()
            ->keyBy(fn (Route $route): string => strtoupper((string) $route->code));

        /** @var Collection<int, Warehouse> $vehicleWarehouses */
        $vehicleWarehouses = Warehouse::query()
            ->where('type', 'vehiculo')
            ->whereIn('route_id', $routes->pluck('id'))
            ->get()
            ->keyBy('route_id');

        $warehouseInitialized = $vehicleWarehouses->mapWithKeys(function (Warehouse $warehouse): array {
            return [$warehouse->id => $this->adjustmentService->hasInventoryActivity($warehouse)];
        });

        DB::transaction(function () use (
            $filteredRows,
            $routes,
            $vehicleWarehouses,
            $warehouseInitialized,
            $user,
            $referenceCode,
            &$processed,
            &$errors,
            &$notificationPayloads
        ): void {
            foreach ($filteredRows as $index => $row) {
                $rowNumber = $index + 2;
                $routeCode = strtoupper(trim((string) ($row['codigo_ruta'] ?? $row['route_code'] ?? '')));
                $productCode = trim((string) ($row['codigo_producto'] ?? $row['codigo'] ?? ''));
                $quantity = $this->normalizeQuantity(
                    $row['cantidad_total'] ?? $row['cantidad_cargada'] ?? $row['cantidad_inicial'] ?? $row['cantidad'] ?? null
                );
                $notes = trim((string) ($row['observaciones'] ?? ''));

                if ($routeCode === '') {
                    $errors[] = "Fila {$rowNumber}: el código de la ruta es obligatorio.";

                    continue;
                }

                if ($productCode === '') {
                    $errors[] = "Fila {$rowNumber}: el código del producto es obligatorio.";

                    continue;
                }

                if ($quantity === null || $quantity < 0) {
                    $errors[] = "Fila {$rowNumber}: la cantidad total debe ser un número entero mayor o igual a cero.";

                    continue;
                }

                $route = $routes->get($routeCode);

                if (! $route instanceof Route) {
                    $errors[] = "Fila {$rowNumber}: no existe una ruta con código {$routeCode}.";

                    continue;
                }

                $vehicleWarehouse = $vehicleWarehouses->get($route->id);

                if (! $vehicleWarehouse instanceof Warehouse) {
                    $errors[] = "Fila {$rowNumber}: la ruta {$routeCode} no tiene bodega de vehículo configurada.";

                    continue;
                }

                $requiresReason = (bool) ($warehouseInitialized->get($vehicleWarehouse->id) ?? false);

                $product = Product::query()
                    ->where('code', $productCode)
                    ->orWhere('worldoffice_code', $productCode)
                    ->first();

                if (! $product instanceof Product) {
                    $errors[] = "Fila {$rowNumber}: no existe un producto con código {$productCode}.";

                    continue;
                }

                $stock = Stock::query()->firstOrNew([
                    'warehouse_id' => $vehicleWarehouse->id,
                    'product_id' => $product->id,
                ]);

                $previousQuantity = (int) ($stock->quantity ?? 0);
                $difference = $quantity - $previousQuantity;

                if ($requiresReason && $difference !== 0 && $notes === '') {
                    $errors[] = "Fila {$rowNumber}: debes registrar observación para ajustar inventario en la ruta {$routeCode}.";

                    continue;
                }

                $stock->quantity = $quantity;
                $stock->min_quantity = (int) $product->min_stock_alert;
                $stock->save();

                if ($difference !== 0) {
                    StockMovement::create([
                        'movement_type' => $requiresReason ? 'ajuste_manual' : 'carga_inicial',
                        'origin_warehouse_id' => $difference < 0 ? $vehicleWarehouse->id : null,
                        'destination_warehouse_id' => $difference >= 0 ? $vehicleWarehouse->id : null,
                        'product_id' => $product->id,
                        'quantity' => abs($difference),
                        'unit_cost' => (float) $product->unit_price,
                        'reference_code' => $referenceCode,
                        'notes' => $notes !== '' ? $notes : "Carga masiva inicial del vehículo {$route->code}",
                        'performed_by' => $user->id,
                    ]);

                    if ($this->adjustmentService->shouldNotifyAdmins($requiresReason, $user, $difference)) {
                        $notificationPayloads[] = [
                            'warehouse' => $vehicleWarehouse,
                            'product' => $product,
                            'old' => $previousQuantity,
                            'new' => $quantity,
                            'reason' => $notes,
                        ];
                    }
                }

                $processed++;
            }
        });

        return [$processed, $errors, $filteredRows->count(), $notificationPayloads];
    }

    /**
     * @param Collection<int, array{warehouse:Warehouse,product:Product,old:int,new:int,reason:string}> $notifications
     */
    private function sendNotifications(User $actor, Collection $notifications): void
    {
        if ($notifications->isEmpty() || ! Schema::hasTable('notifications')) {
            return;
        }

        $recipients = $this->adjustmentService->adminRecipients($actor->id);

        if ($recipients->isEmpty()) {
            return;
        }

        $notifications->each(function (array $payload) use ($actor, $recipients): void {
            Notification::send(
                $recipients,
                new InventoryAdjustmentNotification(
                    actor: $actor,
                    warehouse: $payload['warehouse'],
                    product: $payload['product'],
                    oldQuantity: $payload['old'],
                    newQuantity: $payload['new'],
                    reason: $payload['reason'],
                )
            );
        });
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
