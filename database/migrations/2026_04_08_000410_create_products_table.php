<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('worldoffice_code', 20)->nullable()->index();
            $table->string('name', 150);
            $table->enum('category', ['bebida_fria','bebida_caliente','snack','insumo','otro'])->default('otro');
            $table->string('unit_of_measure', 20)->default('Und.');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('min_stock_alert', 12, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
