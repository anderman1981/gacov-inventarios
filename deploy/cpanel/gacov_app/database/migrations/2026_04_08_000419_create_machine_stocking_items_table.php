<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_stocking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stocking_record_id')->constrained('machine_stocking_records')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity_loaded', 12, 3);
            $table->decimal('physical_count_before', 12, 3)->nullable();
            $table->decimal('physical_count_after', 12, 3)->nullable();
            $table->decimal('difference', 12, 3)->default(0);
            $table->string('notes', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_stocking_items');
    }
};
