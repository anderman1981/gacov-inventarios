<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_sales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('VTA-2026-0001');
            $table->foreignId('machine_id')->constrained('machines');
            $table->foreignId('registered_by')->constrained('users')->comment('Conductor que registra');
            $table->enum('status', ['borrador', 'confirmado', 'exportado_wo'])->default('borrador');
            $table->string('offline_local_id', 60)->nullable()->index();
            $table->boolean('was_offline')->default(false);
            $table->text('notes')->nullable();
            $table->date('sale_date');
            $table->timestamp('exported_to_wo_at')->nullable();
            $table->timestamps();
            $table->index(['machine_id', 'sale_date']);
        });

        Schema::create('machine_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_sale_id')->constrained('machine_sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity_sold', 12, 3)->comment('Unidades vendidas');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->string('notes', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_sale_items');
        Schema::dropIfExists('machine_sales');
    }
};
