<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->enum('movement_type', ['carga_inicial','ajuste_manual','traslado_salida','traslado_entrada','surtido_maquina','conteo_fisico','exportado_wo']);
            $table->foreignId('origin_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('reference_code', 50)->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->constrained('users');
            $table->timestamp('exported_to_wo_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('movement_type');
            $table->index('product_id');
            $table->index('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('stock_movements'); }
};
