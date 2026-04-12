<?php

declare(strict_types=1);

namespace App\Application\UseCase\Machines;

use App\Imports\MachineCatalogImport;
use App\Models\ExcelImport;
use App\Models\Machine;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

final class ImportMachinesHandler
{
    private const ALLOWED_TYPES = [
        'vending_cafe',
        'vending_snack',
        'vending_bebida',
        'mixta',
    ];

    public function handle(UploadedFile $file, User $user): ExcelImport
    {
        $storedPath = $file->store('imports/maquinas');

        $importLog = ExcelImport::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'import_type' => 'maquinas',
            'status' => 'procesando',
            'imported_by' => $user->id,
        ]);

        try {
            $reader = new MachineCatalogImport;
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
            return trim((string) ($row['codigo_maquina'] ?? $row['code'] ?? '')) !== '';
        })->values();

        DB::transaction(function () use ($filteredRows, &$processed, &$errors): void {
            foreach ($filteredRows as $index => $row) {
                $rowNumber = $index + 2;
                $machineCode = strtoupper(trim((string) ($row['codigo_maquina'] ?? $row['code'] ?? '')));
                $machineName = trim((string) ($row['nombre_maquina'] ?? $row['name'] ?? ''));
                $worldOfficeCode = $this->normalizeNullableString($row['codigo_worldoffice'] ?? $row['worldoffice_code'] ?? null);
                $location = $this->normalizeNullableString($row['ubicacion'] ?? $row['location'] ?? null);
                $routeCode = $this->normalizeNullableString($row['codigo_ruta'] ?? $row['route_code'] ?? null);
                $operatorEmail = $this->normalizeNullableString($row['email_operador'] ?? $row['operator_email'] ?? null);
                $machineType = $this->normalizeType($row['tipo'] ?? $row['type'] ?? 'mixta');
                $isActive = $this->normalizeBoolean($row['activa'] ?? $row['is_active'] ?? true);

                if ($machineCode === '') {
                    $errors[] = "Fila {$rowNumber}: el código de la máquina es obligatorio.";

                    continue;
                }

                if ($machineName === '') {
                    $errors[] = "Fila {$rowNumber}: el nombre de la máquina es obligatorio.";

                    continue;
                }

                if ($machineType === null) {
                    $errors[] = "Fila {$rowNumber}: el tipo debe ser vending_cafe, vending_snack, vending_bebida o mixta.";

                    continue;
                }

                $routeId = null;
                if ($routeCode !== null) {
                    $route = Route::query()->where('code', strtoupper($routeCode))->first();

                    if (! $route instanceof Route) {
                        $errors[] = "Fila {$rowNumber}: no existe una ruta con código {$routeCode}.";

                        continue;
                    }

                    $routeId = $route->id;
                }

                $operatorId = null;
                if ($operatorEmail !== null) {
                    $operator = User::query()->where('email', $operatorEmail)->first();

                    if (! $operator instanceof User) {
                        $errors[] = "Fila {$rowNumber}: no existe un usuario con email {$operatorEmail}.";

                        continue;
                    }

                    $operatorId = $operator->id;
                }

                $machine = Machine::query()->updateOrCreate(
                    ['code' => $machineCode],
                    [
                        'worldoffice_code' => $worldOfficeCode,
                        'name' => $machineName,
                        'location' => $location,
                        'route_id' => $routeId,
                        'operator_user_id' => $operatorId,
                        'type' => $machineType,
                        'is_active' => $isActive,
                    ],
                );

                Warehouse::query()->updateOrCreate(
                    [
                        'machine_id' => $machine->id,
                        'type' => 'maquina',
                    ],
                    [
                        'name' => 'Bodega '.$machine->name,
                        'code' => $machine->code,
                        'route_id' => $machine->route_id,
                        'is_active' => $machine->is_active,
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

    private function normalizeType(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        if ($normalized === 'vending_bebida_caliente') {
            $normalized = 'vending_cafe';
        }

        return in_array($normalized, self::ALLOWED_TYPES, true) ? $normalized : null;
    }
}
