<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_order_id')->constrained('transfer_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity_requested', 12, 3);
            $table->decimal('quantity_dispatched', 12, 3)->nullable();
            $table->decimal('quantity_received', 12, 3)->nullable();
            $table->string('notes', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_order_items');
    }
};
