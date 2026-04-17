<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('modules')->where('key', 'invoices')->first();

        if ($existing !== null) {
            DB::table('modules')
                ->where('key', 'invoices')
                ->update([
                    'name' => 'Facturas',
                    'description' => 'Facturación formal y pagos del cliente',
                    'phase_required' => 1,
                    'sort_order' => 10,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('modules')->insert([
            'key' => 'invoices',
            'name' => 'Facturas',
            'description' => 'Facturación formal y pagos del cliente',
            'phase_required' => 1,
            'sort_order' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('modules')->where('key', 'invoices')->delete();
    }
};
