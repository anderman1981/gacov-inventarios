<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('original_file_name', 200);
            $table->string('stored_file_path', 500)->nullable();
            $table->enum('status', ['borrador', 'procesado', 'descartado'])->default('borrador');
            $table->string('supplier', 150)->nullable();
            $table->string('invoice_number', 80)->nullable();
            $table->date('purchase_date')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('total_units')->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('discarded_at')->nullable();
            $table->foreignId('discarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id', 'idx_purchase_import_batches_tenant_id');
            $table->index('status');
            $table->foreign('tenant_id', 'fk_purchase_import_batches_tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        Schema::create('purchase_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('purchase_import_batch_id')->constrained('purchase_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_code', 60);
            $table->string('product_name', 150)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('supplier', 150)->nullable();
            $table->string('invoice_number', 80)->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['valida', 'error'])->default('valida');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'idx_purchase_import_rows_tenant_id');
            $table->index(['purchase_import_batch_id', 'status'], 'idx_purchase_import_rows_batch_status');
            $table->foreign('tenant_id', 'fk_purchase_import_rows_tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        $this->expandStockMovementTypes();
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_import_rows');
        Schema::dropIfExists('purchase_import_batches');

        $this->restoreStockMovementTypes();
    }

    private function expandStockMovementTypes(): void
    {
        if (! $this->canModifyStockMovementEnum()) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE stock_movements
            MODIFY COLUMN movement_type ENUM(
                'carga_inicial',
                'ajuste_manual',
                'traslado_salida',
                'traslado_entrada',
                'surtido_maquina',
                'venta_maquina',
                'conteo_fisico',
                'exportado_wo',
                'compra'
            ) NOT NULL
        SQL);
    }

    private function restoreStockMovementTypes(): void
    {
        if (! $this->canModifyStockMovementEnum()) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE stock_movements
            MODIFY COLUMN movement_type ENUM(
                'carga_inicial',
                'ajuste_manual',
                'traslado_salida',
                'traslado_entrada',
                'surtido_maquina',
                'venta_maquina',
                'conteo_fisico',
                'exportado_wo'
            ) NOT NULL
        SQL);
    }

    private function canModifyStockMovementEnum(): bool
    {
        return Schema::hasTable('stock_movements')
            && DB::connection()->getDriverName() === 'mysql';
    }
};
