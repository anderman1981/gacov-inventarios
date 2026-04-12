<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // INDEX: stock_movements.created_at
        // Impacto: Dashboard y reportes de movimientos
        // Prioridad: CRÍTICO
        // ============================================================
        if (! Schema::hasIndex('stock_movements', 'idx_stock_movements_created_at')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->index(['created_at'], 'idx_stock_movements_created_at');
            });
        }

        // ============================================================
        // INDEX: stock.warehouse_id + quantity (compound)
        // Impacto: Reportes de inventario por bodega
        // Prioridad: ALTO
        // ============================================================
        if (! Schema::hasIndex('stock', 'idx_stock_warehouse_active')) {
            Schema::table('stock', function (Blueprint $table): void {
                $table->index(['warehouse_id', 'quantity'], 'idx_stock_warehouse_active');
            });
        }

        // ============================================================
        // INDEX: transfer_orders.status
        // Impacto: Filtrado de traslados por estado
        // Prioridad: MEDIO
        // ============================================================
        if (! Schema::hasIndex('transfer_orders', 'idx_transfer_orders_status')) {
            Schema::table('transfer_orders', function (Blueprint $table): void {
                $table->index(['status'], 'idx_transfer_orders_status');
            });
        }

        // ============================================================
        // INDEX: stock_movements.movement_type
        // Impacto: Reportes por tipo de movimiento
        // Prioridad: MEDIO
        // ============================================================
        if (! Schema::hasIndex('stock_movements', 'idx_stock_movements_type')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->index(['movement_type'], 'idx_stock_movements_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndexIfExists('idx_stock_movements_created_at');
            $table->dropIndexIfExists('idx_stock_movements_type');
        });

        Schema::table('stock', function (Blueprint $table): void {
            $table->dropIndexIfExists('idx_stock_warehouse_active');
        });

        Schema::table('transfer_orders', function (Blueprint $table): void {
            $table->dropIndexIfExists('idx_transfer_orders_status');
        });
    }
};
