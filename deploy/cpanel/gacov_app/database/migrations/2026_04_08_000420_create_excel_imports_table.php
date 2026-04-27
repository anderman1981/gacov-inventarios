<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', 200);
            $table->string('file_path', 500);
            $table->enum('import_type', ['inventario_inicial', 'productos', 'planilla_surtido', 'ajuste_masivo']);
            $table->enum('status', ['pendiente', 'procesando', 'completado', 'error'])->default('pendiente');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->json('error_log')->nullable();
            $table->foreignId('imported_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excel_imports');
    }
};
