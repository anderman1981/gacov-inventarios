<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega flujo de 3 fases y registro de efectivo al surtido de máquinas.
 *
 * Fases de status:
 *   pendiente_carga  — Inspector registró necesidades, pendiente de cargar vehículo
 *   en_surtido       — Conductor cargó el vehículo, está surtiendo la máquina
 *   completado       — Máquina surtida (estado anterior)
 *   cancelado        — Cancelado
 *
 * Efectivo ingresado A LA MÁQUINA (billetes + monedas colombianas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_stocking_records', function (Blueprint $table): void {
            // Billetes colombianos ingresados a la máquina
            $table->unsignedSmallInteger('bill_100000')->default(0)->after('completed_at');
            $table->unsignedSmallInteger('bill_50000')->default(0)->after('bill_100000');
            $table->unsignedSmallInteger('bill_20000')->default(0)->after('bill_50000');
            $table->unsignedSmallInteger('bill_10000')->default(0)->after('bill_20000');
            $table->unsignedSmallInteger('bill_5000')->default(0)->after('bill_10000');
            $table->unsignedSmallInteger('bill_2000')->default(0)->after('bill_5000');
            $table->unsignedSmallInteger('bill_1000')->default(0)->after('bill_2000');

            // Monedas colombianas ingresadas a la máquina
            $table->unsignedSmallInteger('coin_1000')->default(0)->after('bill_1000');
            $table->unsignedSmallInteger('coin_500')->default(0)->after('coin_1000');
            $table->unsignedSmallInteger('coin_200')->default(0)->after('coin_500');
            $table->unsignedSmallInteger('coin_100')->default(0)->after('coin_200');
            $table->unsignedSmallInteger('coin_50')->default(0)->after('coin_100');

            // Totales calculados
            $table->unsignedBigInteger('total_cash_bills')->default(0)->after('coin_50');
            $table->unsignedBigInteger('total_cash_coins')->default(0)->after('total_cash_bills');
            $table->unsignedBigInteger('total_cash')->default(0)->after('total_cash_coins');

            // Marca que la fase 2 (carga) fue confirmada
            $table->timestamp('loaded_at')->nullable()->after('total_cash');
        });
    }

    public function down(): void
    {
        Schema::table('machine_stocking_records', function (Blueprint $table): void {
            $table->dropColumn([
                'bill_100000', 'bill_50000', 'bill_20000', 'bill_10000',
                'bill_5000', 'bill_2000', 'bill_1000',
                'coin_1000', 'coin_500', 'coin_200', 'coin_100', 'coin_50',
                'total_cash_bills', 'total_cash_coins', 'total_cash',
                'loaded_at',
            ]);
        });
    }
};
