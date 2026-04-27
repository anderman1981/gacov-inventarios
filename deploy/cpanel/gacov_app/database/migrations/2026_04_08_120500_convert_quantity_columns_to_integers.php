<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('UPDATE products SET min_stock_alert = ROUND(min_stock_alert, 0)');
        DB::statement('UPDATE stock SET quantity = ROUND(quantity, 0), min_quantity = ROUND(min_quantity, 0)');
        DB::statement('UPDATE stock_movements SET quantity = ROUND(quantity, 0)');
        DB::statement('UPDATE transfer_order_items SET quantity_requested = ROUND(quantity_requested, 0), quantity_dispatched = ROUND(quantity_dispatched, 0), quantity_received = ROUND(quantity_received, 0)');
        DB::statement('UPDATE machine_stocking_items SET quantity_loaded = ROUND(quantity_loaded, 0), physical_count_before = ROUND(physical_count_before, 0), physical_count_after = ROUND(physical_count_after, 0), difference = ROUND(difference, 0)');
        DB::statement('UPDATE machine_sale_items SET quantity_sold = ROUND(quantity_sold, 0)');

        DB::statement('ALTER TABLE products MODIFY COLUMN min_stock_alert INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE stock MODIFY COLUMN quantity INT UNSIGNED NOT NULL DEFAULT 0, MODIFY COLUMN min_quantity INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE stock_movements MODIFY COLUMN quantity INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE transfer_order_items MODIFY COLUMN quantity_requested INT UNSIGNED NOT NULL, MODIFY COLUMN quantity_dispatched INT UNSIGNED NULL, MODIFY COLUMN quantity_received INT UNSIGNED NULL');
        DB::statement('ALTER TABLE machine_stocking_items MODIFY COLUMN quantity_loaded INT UNSIGNED NOT NULL, MODIFY COLUMN physical_count_before INT UNSIGNED NULL, MODIFY COLUMN physical_count_after INT UNSIGNED NULL, MODIFY COLUMN difference INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE machine_sale_items MODIFY COLUMN quantity_sold INT UNSIGNED NOT NULL COMMENT "Unidades vendidas"');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE products MODIFY COLUMN min_stock_alert DECIMAL(12, 3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE stock MODIFY COLUMN quantity DECIMAL(12, 3) NOT NULL DEFAULT 0, MODIFY COLUMN min_quantity DECIMAL(12, 3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE stock_movements MODIFY COLUMN quantity DECIMAL(12, 3) NOT NULL');
        DB::statement('ALTER TABLE transfer_order_items MODIFY COLUMN quantity_requested DECIMAL(12, 3) NOT NULL, MODIFY COLUMN quantity_dispatched DECIMAL(12, 3) NULL, MODIFY COLUMN quantity_received DECIMAL(12, 3) NULL');
        DB::statement('ALTER TABLE machine_stocking_items MODIFY COLUMN quantity_loaded DECIMAL(12, 3) NOT NULL, MODIFY COLUMN physical_count_before DECIMAL(12, 3) NULL, MODIFY COLUMN physical_count_after DECIMAL(12, 3) NULL, MODIFY COLUMN difference DECIMAL(12, 3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE machine_sale_items MODIFY COLUMN quantity_sold DECIMAL(12, 3) NOT NULL COMMENT "Unidades vendidas"');
    }
};
