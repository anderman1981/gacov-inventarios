<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->canModifyEnumColumn()) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE excel_imports
            MODIFY COLUMN import_type ENUM(
                'inventario_inicial',
                'inventario_vehiculos',
                'inventario_maquinas_inicial',
                'productos',
                'planilla_surtido',
                'ajuste_masivo',
                'maquinas',
                'rutas'
            ) NOT NULL
        SQL);
    }

    public function down(): void
    {
        if (! $this->canModifyEnumColumn()) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE excel_imports
            MODIFY COLUMN import_type ENUM(
                'inventario_inicial',
                'productos',
                'planilla_surtido',
                'ajuste_masivo'
            ) NOT NULL
        SQL);
    }

    private function canModifyEnumColumn(): bool
    {
        return Schema::hasTable('excel_imports')
            && DB::connection()->getDriverName() === 'mysql';
    }
};
