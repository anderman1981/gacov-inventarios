<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();

            // Numeración oficial
            $table->string('prefix', 10)->default('INV');
            $table->unsignedInteger('number')->unique();
            $table->string('full_number', 50)->unique(); // INV-2026-000001

            // Fechas
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('paid_at')->nullable();

            // Estados
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled', 'expired'])
                ->default('draft');
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue'])
                ->default('pending');

            // Totales
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance_due', 14, 2)->default(0);

            // Relaciones
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained(); // Cliente que solicita
            $table->foreignId('created_by')->nullable()->constrained('users'); // Admin que crea

            // Emisor (datos de la empresa)
            $table->string('issuer_name', 200);
            $table->string('issuer_nit', 50);
            $table->string('issuer_address', 500)->nullable();
            $table->string('issuer_phone', 50)->nullable();
            $table->string('issuer_email', 100)->nullable();
            $table->string('issuer_logo_path', 500)->nullable();

            // Cliente (del tenant)
            $table->string('client_name', 200);
            $table->string('client_nit', 50);
            $table->string('client_address', 500)->nullable();
            $table->string('client_email', 100)->nullable();
            $table->string('client_phone', 50)->nullable();

            // Notas y términos
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            // Metadatos DIAN (Colombia)
            $table->string('dian_sequential_code', 100)->nullable();
            $table->string('dian_resolution_number', 50)->nullable();
            $table->date('dian_from_date')->nullable();
            $table->date('dian_to_date')->nullable();

            // PDF path
            $table->string('pdf_path', 500)->nullable();

            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'issue_date']);
            $table->index(['tenant_id', 'payment_status']);
            $table->index('full_number');
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Concepto
            $table->string('description', 500);
            $table->string('product_key', 100)->nullable(); // Código del producto/servicio
            $table->string('unit', 20)->default('UN'); // UN, H, M2, M3, KG, etc.

            // Cantidades
            $table->decimal('quantity', 14, 4)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);

            // Totales del item
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // Para servicios recurrentes
            $table->string('billing_period', 50)->nullable(); // mensual, anual, etc.
            $table->date('service_start')->nullable();
            $table->date('service_end')->nullable();

            // Referencia a módulo/plan si aplica
            $table->string('module_key', 100)->nullable();
            $table->string('plan_name', 100)->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invoice_id', 'sort_order']);
        });

        // Pagos de facturas
        Schema::create('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('payment_method', 50)->nullable(); // transferencia, pse, efectivo, etc.
            $table->string('reference', 100)->nullable(); // Número de referencia bancaria
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
