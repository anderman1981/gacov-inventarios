<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type ENUM(
            'carga_inicial','ajuste_manual','traslado_salida','traslado_entrada',
            'surtido_maquina','venta_maquina','conteo_fisico','exportado_wo'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type ENUM(
            'carga_inicial','ajuste_manual','traslado_salida','traslado_entrada',
            'surtido_maquina','conteo_fisico','exportado_wo'
        ) NOT NULL");
    }
};
