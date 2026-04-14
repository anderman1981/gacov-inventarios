<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table): void {
            // Geolocalización de la máquina
            $table->decimal('latitude', 10, 8)->nullable()->after('location');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            // Índices para geolocalización
            $table->index(['latitude', 'longitude']);
        });

        Schema::table('machine_stocking_records', function (Blueprint $table): void {
            // Geolocalización del reporte de surtido
            $table->decimal('latitude', 10, 8)->nullable()->after('notes');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('geolocation_accuracy', 20)->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('machine_stocking_records', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude', 'geolocation_accuracy']);
        });

        Schema::table('machines', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
