<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('cost', 12, 2)->default(0)->after('unit_of_measure');
            $table->decimal('min_sale_price', 12, 2)->default(0)->after('cost');
            $table->string('supplier', 150)->nullable()->after('min_sale_price');
            $table->string('supplier_sku', 60)->nullable()->after('supplier');
            $table->date('expiration_date')->nullable()->after('supplier_sku');
            $table->date('purchase_date')->nullable()->after('expiration_date');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'cost',
                'min_sale_price',
                'supplier',
                'supplier_sku',
                'expiration_date',
                'purchase_date',
            ]);
        });
    }
};
