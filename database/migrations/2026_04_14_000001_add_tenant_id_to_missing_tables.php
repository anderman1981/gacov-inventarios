<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // machine_sales — MachineSale usa BelongsToTenant pero faltaba la columna
        if (Schema::hasTable('machine_sales') && !Schema::hasColumn('machine_sales', 'tenant_id')) {
            Schema::table('machine_sales', function (Blueprint $table): void {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id', 'machine_sales_tenant_id_idx');
            });
        }

        // machine_sale_items — hereda el scope por machine_sale_id pero agrego columna por consistencia
        if (Schema::hasTable('machine_sale_items') && !Schema::hasColumn('machine_sale_items', 'tenant_id')) {
            Schema::table('machine_sale_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id', 'machine_sale_items_tenant_id_idx');
            });
        }

        // transfer_order_items — TransferOrderItem usa BelongsToTenant pero faltaba la columna
        if (Schema::hasTable('transfer_order_items') && !Schema::hasColumn('transfer_order_items', 'tenant_id')) {
            Schema::table('transfer_order_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id', 'transfer_order_items_tenant_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('machine_sales', 'tenant_id')) {
            Schema::table('machine_sales', fn (Blueprint $t) => $t->dropColumn('tenant_id'));
        }
        if (Schema::hasColumn('machine_sale_items', 'tenant_id')) {
            Schema::table('machine_sale_items', fn (Blueprint $t) => $t->dropColumn('tenant_id'));
        }
        if (Schema::hasColumn('transfer_order_items', 'tenant_id')) {
            Schema::table('transfer_order_items', fn (Blueprint $t) => $t->dropColumn('tenant_id'));
        }
    }
};
