<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_sales', function (Blueprint $table): void {
            $table->unsignedBigInteger('cash_bills')->default(0)->after('sale_date')->comment('Total billetes recaudados');
            $table->unsignedBigInteger('cash_coins')->default(0)->after('cash_bills')->comment('Total monedas recaudadas');
            $table->unsignedBigInteger('cash_total')->default(0)->after('cash_coins')->comment('Total efectivo recaudado');
        });
    }
    public function down(): void
    {
        Schema::table('machine_sales', function (Blueprint $table): void {
            $table->dropColumn(['cash_bills','cash_coins','cash_total']);
        });
    }
};
