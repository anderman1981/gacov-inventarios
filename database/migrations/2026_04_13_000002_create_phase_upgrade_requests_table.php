<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_upgrade_requests', function (Blueprint $table): void {
            $table->id();

            // Tenant solicitante
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Usuario que solicitó
            $table->foreignId('requested_by')->constrained('users');

            // Usuario que aprobó/rechazó
            $table->foreignId('approved_by')->nullable()->constrained('users');

            // Fases
            $table->unsignedTinyInteger('current_phase');        // Fase actual antes del request
            $table->unsignedTinyInteger('requested_phase');      // Fase que solicita el cliente
            $table->unsignedTinyInteger('approved_phase')->nullable(); // Fase finalmente aprobada

            // Estado del workflow
            $table->enum('status', [
                'pending',    // Esperando aprobación
                'approved',   // Aprobada, lista para ejecutar
                'rejected',   // Rechazada
                'executed',   // Ejecutada (upgrade completado)
                'cancelled',  // Cancelada por el cliente
            ])->default('pending');

            // Notas
            $table->text('notes')->nullable();         // Notas del cliente
            $table->text('admin_notes')->nullable();    // Notas del admin

            // Timestamps del workflow
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_upgrade_requests');
    }
};
