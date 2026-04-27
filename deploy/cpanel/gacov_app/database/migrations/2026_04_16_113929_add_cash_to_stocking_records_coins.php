<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Actualiza el ENUM de status para incluir las nuevas fases del flujo de surtido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // MySQL: modificar ENUM para agregar nuevos valores de status
        DB::statement("
            ALTER TABLE machine_stocking_records
            MODIFY COLUMN status ENUM(
                'pendiente_carga',
                'en_surtido',
                'completado',
                'cancelado'
            ) NOT NULL DEFAULT 'pendiente_carga'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE machine_stocking_records
            MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'completado'
        ");
    }
};
