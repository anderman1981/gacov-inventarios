<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_cash_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes');
            $table->foreignId('driver_user_id')->constrained('users');
            $table->foreignId('delivered_by_user_id')->constrained('users');
            $table->date('delivery_date');
            $table->unsignedSmallInteger('bill_100000')->default(0);
            $table->unsignedSmallInteger('bill_50000')->default(0);
            $table->unsignedSmallInteger('bill_20000')->default(0);
            $table->unsignedSmallInteger('bill_10000')->default(0);
            $table->unsignedSmallInteger('bill_5000')->default(0);
            $table->unsignedSmallInteger('bill_2000')->default(0);
            $table->unsignedSmallInteger('bill_1000')->default(0);
            $table->unsignedSmallInteger('coin_1000')->default(0);
            $table->unsignedSmallInteger('coin_500')->default(0);
            $table->unsignedSmallInteger('coin_200')->default(0);
            $table->unsignedSmallInteger('coin_100')->default(0);
            $table->unsignedSmallInteger('coin_50')->default(0);
            $table->unsignedBigInteger('total_bills')->default(0);
            $table->unsignedBigInteger('total_coins')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','route_id','delivery_date'],'idx_cash_route_date');
            $table->index(['tenant_id','driver_user_id'],'idx_cash_driver');
        });
    }
    public function down(): void { Schema::dropIfExists('driver_cash_deliveries'); }
};
