<?php

declare(strict_types=1);

namespace App\Application\UseCase\Machines;

use App\Imports\RouteCatalogImport;
use App\Models\ExcelImport;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

final class ImportRoutesHandler
{
    public function handle(UploadedFile $file, User $user): ExcelImport
    {
        $storedPath = $file->store('imports/rutas');

        $importLog = ExcelImport::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'import_type' => 'rutas',
            'status' => 'procesando',
            'imported_by' => $user->id,
        ]);

        try {
            $reader = new RouteCatalogImport();
            Excel::import($reader, $file);

            [$processed, $errors, $totalRows] = $this->processRows($reader->rows());

            $importLog->update([
                'status' => $processed > 0 && $errors === [] ? 'completado' : ($processed > 0 ? 'completado' : 'error'),
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
    private function processRows(Collection $rows): array
    {
        $processed = 0;
        $errors = [];
        $filteredRows = $rows->filter(function ($row): bool {
            return trim((string) ($row['codigo_ruta'] ?? $row['code'] ?? '')) !== '';
        })->values();

        DB::transaction(function () use ($filteredRows, &$processed, &$errors): void {
            foreach ($filteredRows as $index => $row) {
                $rowNumber = $index + 2;
                $routeCode = strtoupper(trim((string) ($row['codigo_ruta'] ?? $row['code'] ?? '')));
                $routeName = trim((string) ($row['nombre_ruta'] ?? $row['name'] ?? ''));
                $vehiclePlate = $this->normalizeNullableString($row['placa_vehiculo'] ?? $row['vehicle_plate'] ?? null);
                $driverEmail = $this->normalizeNullableString($row['email_conductor'] ?? $row['driver_email'] ?? null);
                $isActive = $this->normalizeBoolean($row['activa'] ?? $row['is_active'] ?? true);

                if ($routeCode === '') {
                    $errors[] = "Fila {$rowNumber}: el código de la ruta es obligatorio.";
                    continue;
                }

                if ($routeName === '') {
                    $errors[] = "Fila {$rowNumber}: el nombre de la ruta es obligatorio.";
                    continue;
                }

                $driver = null;
                if ($driverEmail !== null) {
                    $driver = User::query()->where('email', $driverEmail)->first();

                    if (! $driver instanceof User) {
                        $errors[] = "Fila {$rowNumber}: no existe un usuario con email {$driverEmail}.";
                        continue;
                    }
                }

                $route = Route::query()->updateOrCreate(
                    ['code' => $routeCode],
                    [
                        'name' => $routeName,
                        'vehicle_plate' => $vehiclePlate,
                        'driver_user_id' => $driver?->id,
                        'is_active' => $isActive,
                    ],
                );

                Warehouse::query()->updateOrCreate(
                    [
                        'route_id' => $route->id,
                        'type' => 'vehiculo',
                    ],
                    [
                        'name' => 'Vehículo ' . $route->name,
                        'code' => $route->code,
                        'is_active' => $route->is_active,
                    ],
                );

                $processed++;
            }
        });

        return [$processed, $errors, $filteredRows->count()];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'si', 'sí', 'true', 'activo', 'activa'], true);
    }
}
