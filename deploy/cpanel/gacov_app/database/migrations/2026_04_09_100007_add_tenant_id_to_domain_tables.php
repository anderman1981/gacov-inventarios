<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'routes',
        'machines',
        'warehouses',
        'products',
        'stock',
        'stock_movements',
        'transfer_orders',
        'machine_stocking_records',
        'machine_sales',
        'excel_imports',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                    $blueprint->unsignedBigInteger('tenant_id')->nullable()->after('id');
                    $blueprint->index('tenant_id', 'idx_'.$table.'_tenant_id');
                    // FK nombrada según convención: fk_tabla_tenant_id
                    // RESTRICT evita borrar un tenant que tenga datos activos
                    $blueprint->foreign('tenant_id', 'fk_'.$table.'_tenant_id')
                        ->references('id')
                        ->on('tenants')
                        ->restrictOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                    $blueprint->dropForeign('fk_'.$table.'_tenant_id');
                    $blueprint->dropIndex('idx_'.$table.'_tenant_id');
                    $blueprint->dropColumn('tenant_id');
                });
            }
        }
    }
};
