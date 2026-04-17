<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed de datos ficticios para desarrollo/pruebas.
 * Usa las rutas y máquinas REALES ya existentes en el sistema.
 * Solo agrega: stock, asignaciones diarias, entregas de efectivo,
 * historial de ventas y surtidos completados.
 *
 * Ejecución: php artisan db:seed --class=DevDataSeeder
 * Es idempotente: puede correrse varias veces sin duplicar registros.
 */
final class DevDataSeeder extends Seeder
{
    private const TENANT_ID = 1;

    // Usuarios reales del sistema
    private const USER_ADMIN   = 76;  // Administrador GACOV
    private const USER_OSVALDO = 77;  // Conductor (Ruta 2 - RT2)
    private const USER_ANDRES  = 78;  // Conductor (Ruta 1 - RT1)

    // Rutas reales del sistema
    private const ROUTE_RT1 = 21; // RT1 - Ruta 1 (driver: Andres)
    private const ROUTE_RT2 = 22; // RT2 - Ruta 2 (driver: Osvaldo)

    // Bodegas de vehículo reales
    private const VH_RT1 = 16; // Vehículo Ruta 1
    private const VH_RT2 = 17; // Vehículo Ruta 2

    // Bodega principal
    private const BODEGA_PRINCIPAL = 15;

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════╗');
        $this->command->info('║   DevDataSeeder — GACOV Inventarios  ║');
        $this->command->info('║   Usando rutas y máquinas REALES      ║');
        $this->command->info('╚══════════════════════════════════════╝');

        // Asegurar que los productos base estén cargados
        if (DB::table('products')->count() === 0) {
            $this->call(ProductSeeder::class);
        }

        $this->updateProductPrices();

        $machineMap = $this->loadRealMachines();
        $warehouseMap = $this->loadRealWarehouses();

        $this->seedStock($warehouseMap);
        $this->seedRouteScheduleAssignments();
        $this->seedCashDeliveries();
        $this->seedHistoricalSales($machineMap);
        $this->seedHistoricalStockings($machineMap, $warehouseMap);

        $this->command->info('');
        $this->command->info('✅  DevDataSeeder completado exitosamente.');
        $this->command->info('');
        $this->command->table(
            ['Tabla', 'Total registros'],
            [
                ['routes',                   DB::table('routes')->count()],
                ['machines',                 DB::table('machines')->count()],
                ['warehouses',               DB::table('warehouses')->count()],
                ['stock',                    DB::table('stock')->count()],
                ['machine_sales',            DB::table('machine_sales')->count()],
                ['machine_sale_items',       DB::table('machine_sale_items')->count()],
                ['machine_stocking_records', DB::table('machine_stocking_records')->count()],
                ['route_schedule_assignments', DB::table('route_schedule_assignments')->count()],
                ['driver_cash_deliveries',   DB::table('driver_cash_deliveries')->count()],
            ]
        );
    }

    // ─────────────────────────────────────────────
    //  Carga de datos reales del sistema
    // ─────────────────────────────────────────────

    /**
     * Retorna mapa [machine_id => route_id] de las 49 máquinas reales.
     */
    private function loadRealMachines(): array
    {
        $machines = DB::table('machines')
            ->where('tenant_id', self::TENANT_ID)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'route_id']);

        $map = [];
        foreach ($machines as $m) {
            $map[$m->id] = $m->route_id;
        }

        $this->command->info('  → ' . count($map) . ' máquinas reales cargadas.');

        return $map;
    }

    /**
     * Retorna mapa [warehouse_id => ['type','route_id','machine_id']]
     * de todas las bodegas reales.
     */
    private function loadRealWarehouses(): array
    {
        $warehouses = DB::table('warehouses')
            ->where('tenant_id', self::TENANT_ID)
            ->orderBy('id')
            ->get(['id', 'type', 'route_id', 'machine_id']);

        $map = [];
        foreach ($warehouses as $w) {
            $map[$w->id] = [
                'type'       => $w->type,
                'route_id'   => $w->route_id,
                'machine_id' => $w->machine_id,
            ];
        }

        $this->command->info('  → ' . count($map) . ' bodegas reales cargadas.');

        return $map;
    }

    // ─────────────────────────────────────────────
    //  Precios de costo en productos
    // ─────────────────────────────────────────────

    private function updateProductPrices(): void
    {
        $prices = [
            '5'   => 800,   '6'   => 900,   '7'   => 850,   '8'   => 950,
            '14'  => 750,   '16'  => 900,   '26'  => 850,   '40'  => 600,
            '48'  => 700,   '51'  => 950,   '54'  => 500,   '124' => 1100,
            '130' => 1200,  '497' => 700,   '508' => 600,   '623' => 750,
            '687' => 800,   '694' => 950,   '705' => 500,   '714' => 900,
            '716' => 850,   '721' => 800,   '722' => 700,   '723' => 900,
            '724' => 900,   '725' => 700,   '730' => 850,   '731' => 900,
            '732' => 550,   '63'  => 1500,  '64'  => 2200,  '65'  => 900,
            '66'  => 1400,  '68'  => 2200,  '73'  => 2500,  '82'  => 2000,
            '86'  => 1800,  '87'  => 1200,  '102' => 1800,  '104' => 2500,
            '126' => 2000,  '317' => 1600,  '486' => 2200,  '621' => 600,
            '637' => 2400,  '718' => 2200,  '720' => 1800,  '795' => 1500,
            '110' => 45000, '113' => 28000, '114' => 18000, '115' => 22000,
            '117' => 22000, '118' => 300,   '119' => 150,   '121' => 200,
            '640' => 45000, '660' => 6000,  '711' => 8000,
        ];

        foreach ($prices as $code => $price) {
            DB::table('products')
                ->where('code', (string) $code)
                ->update(['unit_price' => $price]);
        }

        $this->command->info('  → Precios de costo actualizados en ' . count($prices) . ' productos.');
    }

    // ─────────────────────────────────────────────
    //  Stock en todas las bodegas
    // ─────────────────────────────────────────────

    private function seedStock(array $warehouseMap): void
    {
        $now      = now();
        $products = DB::table('products')
            ->where('is_active', true)
            ->get(['id', 'code', 'category']);

        $inserted = 0;
        $updated  = 0;

        foreach ($warehouseMap as $warehouseId => $wh) {
            foreach ($products as $product) {
                [$qty, $minQty] = $this->stockQtyForWarehouse($wh['type'], $product->category);

                $exists = DB::table('stock')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)
                    ->exists();

                if ($exists) {
                    // Solo actualizar si está en 0 para no pisar datos reales con valor
                    $current = (int) DB::table('stock')
                        ->where('warehouse_id', $warehouseId)
                        ->where('product_id', $product->id)
                        ->value('quantity');

                    if ($current === 0) {
                        DB::table('stock')
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_id', $product->id)
                            ->update(['quantity' => $qty, 'min_quantity' => $minQty, 'updated_at' => $now]);
                        $updated++;
                    }
                } else {
                    DB::table('stock')->insert([
                        'tenant_id'    => self::TENANT_ID,
                        'warehouse_id' => $warehouseId,
                        'product_id'   => $product->id,
                        'quantity'     => $qty,
                        'min_quantity' => $minQty,
                        'updated_at'   => $now,
                    ]);
                    $inserted++;
                }
            }
        }

        $this->command->info("  → Stock: {$inserted} registros nuevos, {$updated} actualizados (solo los que estaban en 0).");
    }

    private function stockQtyForWarehouse(string $type, string $category): array
    {
        // Distribuye con variedad real: ~20% cero, ~20% bajo (en alerta), ~60% normal/alto
        $roll = rand(1, 10);

        return match($type) {
            'bodega' => match($category) {
                'insumo'      => $roll <= 1 ? [0,  8]  : ($roll <= 3 ? [rand(2, 7),  8]  : [rand(20, 60),  8]),
                'bebida_fria' => $roll <= 1 ? [0,  30] : ($roll <= 3 ? [rand(5, 25), 30] : [rand(100, 400), 30]),
                default       => $roll <= 1 ? [0,  40] : ($roll <= 3 ? [rand(5, 35), 40] : [rand(150, 600), 40]),
            },
            'vehiculo' => match($category) {
                'insumo'      => $roll <= 2 ? [0, 5] : ($roll <= 4 ? [rand(1, 4), 5] : [rand(8, 22),  5]),
                'bebida_fria' => $roll <= 2 ? [0, 5] : ($roll <= 4 ? [rand(1, 4), 5] : [rand(15, 70), 5]),
                default       => $roll <= 2 ? [0, 5] : ($roll <= 4 ? [rand(1, 4), 5] : [rand(20, 90), 5]),
            },
            'maquina' => match($category) {
                'insumo'      => $roll <= 3 ? [0, 2] : ($roll <= 5 ? [rand(1, 2), 2] : [rand(3, 10),  2]),
                'bebida_fria' => $roll <= 3 ? [0, 3] : ($roll <= 5 ? [rand(1, 3), 3] : [rand(5, 25),  3]),
                default       => $roll <= 3 ? [0, 3] : ($roll <= 5 ? [rand(1, 3), 3] : [rand(5, 30),  3]),
            },
            default => $roll <= 2 ? [0, 5] : [rand(5, 50), 5],
        };
    }

    // ─────────────────────────────────────────────
    //  Asignaciones de ruta (últimos 14 días + hoy)
    // ─────────────────────────────────────────────

    private function seedRouteScheduleAssignments(): void
    {
        $now     = now();
        $created = 0;

        $assignments = [
            // [route_id, driver_user_id]
            [self::ROUTE_RT1, self::USER_ANDRES],
            [self::ROUTE_RT2, self::USER_OSVALDO],
        ];

        for ($offset = -14; $offset <= 0; $offset++) {
            $date = now()->addDays($offset)->toDateString();

            foreach ($assignments as [$routeId, $driverId]) {
                $exists = DB::table('route_schedule_assignments')
                    ->where('tenant_id', self::TENANT_ID)
                    ->where('route_id', $routeId)
                    ->where('assignment_date', $date)
                    ->where('driver_user_id', $driverId)
                    ->exists();

                if (! $exists) {
                    DB::table('route_schedule_assignments')->insert([
                        'tenant_id'           => self::TENANT_ID,
                        'route_id'            => $routeId,
                        'assignment_date'     => $date,
                        'driver_user_id'      => $driverId,
                        'assigned_by_user_id' => self::USER_ADMIN,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ]);
                    $created++;
                }
            }
        }

        $this->command->info("  → {$created} asignaciones de ruta creadas (últimos 14 días + hoy).");
    }

    // ─────────────────────────────────────────────
    //  Entregas de efectivo (últimos 14 días)
    // ─────────────────────────────────────────────

    private function seedCashDeliveries(): void
    {
        $now     = now();
        $created = 0;

        // Escenarios: [route_id, driver_id, day_offset, b100k, b50k, b20k, b10k, b5k, b2k, c1k, c500, c200, c100, c50]
        $scenarios = [
            [self::ROUTE_RT1, self::USER_ANDRES,  -13, 2, 3, 5, 8, 4, 6, 10, 20, 15, 30, 10],
            [self::ROUTE_RT2, self::USER_OSVALDO, -13, 1, 4, 6, 5, 3, 4,  8, 15, 20, 25,  5],
            [self::ROUTE_RT1, self::USER_ANDRES,  -11, 3, 2, 4, 6, 5, 8, 12, 18, 10, 20,  0],
            [self::ROUTE_RT2, self::USER_OSVALDO, -11, 2, 3, 5, 7, 4, 5, 10, 20, 15, 15, 10],
            [self::ROUTE_RT1, self::USER_ANDRES,   -8, 4, 5, 6, 4, 3, 2,  8, 12, 18, 20,  5],
            [self::ROUTE_RT2, self::USER_OSVALDO,  -8, 2, 2, 8, 5, 6, 4, 15, 10, 12, 18,  0],
            [self::ROUTE_RT1, self::USER_ANDRES,   -6, 3, 4, 5, 6, 4, 3, 10, 16, 14, 22,  8],
            [self::ROUTE_RT2, self::USER_OSVALDO,  -6, 1, 3, 4, 8, 5, 6,  8, 18, 20, 10,  5],
            [self::ROUTE_RT1, self::USER_ANDRES,   -4, 2, 4, 6, 5, 3, 3, 12, 14, 16, 18,  0],
            [self::ROUTE_RT2, self::USER_OSVALDO,  -4, 3, 2, 7, 4, 5, 4,  9, 22, 11, 20,  5],
            [self::ROUTE_RT1, self::USER_ANDRES,   -2, 4, 3, 5, 7, 4, 4, 11, 16, 15, 25,  0],
            [self::ROUTE_RT2, self::USER_OSVALDO,  -2, 2, 4, 4, 6, 3, 5, 10, 18, 12, 16, 10],
            [self::ROUTE_RT1, self::USER_ANDRES,    0, 2, 3, 6, 5, 4, 4, 12, 14, 16, 24,  0],
            [self::ROUTE_RT2, self::USER_OSVALDO,   0, 3, 4, 5, 6, 3, 5, 10, 20, 12, 18, 10],
        ];

        $denomFields = [
            'bill_100000' => 100000, 'bill_50000' => 50000, 'bill_20000' => 20000,
            'bill_10000'  => 10000,  'bill_5000'  => 5000,  'bill_2000'  => 2000,
            'coin_1000'   => 1000,   'coin_500'   => 500,   'coin_200'   => 200,
            'coin_100'    => 100,    'coin_50'    => 50,
        ];

        foreach ($scenarios as $s) {
            [$routeId, $driverId, $offset, $b100, $b50, $b20, $b10, $b5, $b2, $c1k, $c500, $c200, $c100, $c50] = $s;
            $date  = now()->addDays($offset)->toDateString();
            $qtys  = [$b100, $b50, $b20, $b10, $b5, $b2, $c1k, $c500, $c200, $c100, $c50];

            $exists = DB::table('driver_cash_deliveries')
                ->where('tenant_id', self::TENANT_ID)
                ->where('route_id', $routeId)
                ->where('driver_user_id', $driverId)
                ->where('delivery_date', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            $row    = ['tenant_id' => self::TENANT_ID, 'route_id' => $routeId, 'driver_user_id' => $driverId, 'delivery_date' => $date];
            $bills  = 0;
            $coins  = 0;
            $idx    = 0;

            foreach ($denomFields as $field => $value) {
                $qty         = $qtys[$idx++];
                $row[$field] = $qty;
                if (str_starts_with($field, 'bill')) {
                    $bills += $qty * $value;
                } else {
                    $coins += $qty * $value;
                }
            }

            $row['total_bills']          = $bills;
            $row['total_coins']          = $coins;
            $row['total_amount']         = $bills + $coins;
            $row['delivered_by_user_id'] = self::USER_ADMIN;
            $row['notes']                = null;
            $row['created_at']           = $now;
            $row['updated_at']           = $now;

            DB::table('driver_cash_deliveries')->insert($row);
            $created++;
        }

        $this->command->info("  → {$created} entregas de efectivo creadas.");
    }

    // ─────────────────────────────────────────────
    //  Ventas históricas (últimos 30 días)
    // ─────────────────────────────────────────────

    private function seedHistoricalSales(array $machineMap): void
    {
        $now = now();

        $saleProducts = DB::table('products')
            ->whereIn('category', ['snack', 'bebida_fria'])
            ->where('is_active', true)
            ->get(['id', 'unit_price', 'category']);

        if ($saleProducts->isEmpty()) {
            $this->command->warn('  ! Sin productos de venta — omitiendo ventas históricas.');
            return;
        }

        $getUnitPrice = function (object $product): int {
            $cost    = (float) ($product->unit_price ?? 1000);
            $markup  = $product->category === 'bebida_fria' ? 1.55 : 1.75;
            return (int) (round($cost * $markup / 100) * 100);
        };

        $saleCount = 0;

        foreach ($machineMap as $machineId => $routeId) {
            $driver = ($routeId === self::ROUTE_RT1) ? self::USER_ANDRES : self::USER_OSVALDO;

            for ($day = 30; $day >= 1; $day--) {
                $saleDate = now()->subDays($day)->toDateString();
                $dow      = (int) now()->subDays($day)->format('N');

                // Menos ventas en fin de semana
                if ($dow >= 6 && rand(0, 10) < 5) {
                    continue;
                }

                $exists = DB::table('machine_sales')
                    ->where('tenant_id', self::TENANT_ID)
                    ->where('machine_id', $machineId)
                    ->where('sale_date', $saleDate)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $cashBills = rand(1, 5) * 10000;
                $cashCoins = rand(0, 4) * 1000;
                $code      = 'VTA-' . strtoupper(substr(md5($machineId . $saleDate), 0, 7));

                $saleId = DB::table('machine_sales')->insertGetId([
                    'tenant_id'    => self::TENANT_ID,
                    'code'         => $code,
                    'machine_id'   => $machineId,
                    'registered_by' => $driver,
                    'status'       => 'confirmado',
                    'was_offline'  => false,
                    'sale_date'    => $saleDate,
                    'cash_bills'   => $cashBills,
                    'cash_coins'   => $cashCoins,
                    'cash_total'   => $cashBills + $cashCoins,
                    'notes'        => null,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);

                // 3-6 productos por venta
                $selected = $saleProducts->shuffle()->take(rand(3, min(6, $saleProducts->count())));

                foreach ($selected as $product) {
                    DB::table('machine_sale_items')->insert([
                        'tenant_id'       => self::TENANT_ID,
                        'machine_sale_id' => $saleId,
                        'product_id'      => $product->id,
                        'quantity_sold'   => rand(1, 6),
                        'unit_price'      => $getUnitPrice($product),
                        'notes'           => null,
                    ]);
                }

                $saleCount++;
            }
        }

        $this->command->info("  → {$saleCount} ventas históricas generadas (últimos 30 días).");
    }

    // ─────────────────────────────────────────────
    //  Surtidos históricos completados
    // ─────────────────────────────────────────────

    private function seedHistoricalStockings(array $machineMap, array $warehouseMap): void
    {
        $now      = now();
        $products = DB::table('products')->where('is_active', true)->pluck('id')->toArray();

        // Mapa machine_id → warehouse_id (bodegas tipo maquina)
        $machineWhMap = [];
        foreach ($warehouseMap as $whId => $wh) {
            if ($wh['type'] === 'maquina' && $wh['machine_id']) {
                $machineWhMap[(int) $wh['machine_id']] = $whId;
            }
        }

        $stockingCount = 0;

        foreach ($machineMap as $machineId => $routeId) {
            // Saltar máquinas sin ruta asignada (no tienen bodega de vehículo)
            if (! $routeId) {
                continue;
            }

            $machineWhId = $machineWhMap[$machineId] ?? null;
            if (! $machineWhId) {
                continue;
            }

            $isRt1       = ($routeId === self::ROUTE_RT1);
            $driver      = $isRt1 ? self::USER_ANDRES  : self::USER_OSVALDO;
            $vehicleWhId = $isRt1 ? self::VH_RT1       : self::VH_RT2;

            // 1 surtido por semana de las últimas 4 semanas
            foreach ([28, 21, 14, 7] as $daysAgo) {
                $startedAt   = now()->subDays($daysAgo)->setHour(rand(7, 11))->setMinute(rand(0, 59));
                $loadedAt    = (clone $startedAt)->addMinutes(rand(10, 25));
                $completedAt = (clone $loadedAt)->addMinutes(rand(15, 35));

                $code = 'SURT-' . strtoupper(substr(md5($machineId . $daysAgo . 'v2'), 0, 7));

                $exists = DB::table('machine_stocking_records')
                    ->where('tenant_id', self::TENANT_ID)
                    ->where('code', $code)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $bills = [
                    'bill_100000' => rand(0, 2), 'bill_50000' => rand(1, 4),
                    'bill_20000'  => rand(2, 6), 'bill_10000' => rand(3, 8),
                    'bill_5000'   => rand(2, 5), 'bill_2000'  => rand(1, 4),
                    'bill_1000'   => 0,
                ];
                $coins = [
                    'coin_1000' => rand(5, 20), 'coin_500'  => rand(10, 30),
                    'coin_200'  => rand(5, 20), 'coin_100'  => rand(10, 40),
                    'coin_50'   => rand(0, 10),
                ];

                $totalBills = 0;
                foreach (['bill_100000' => 100000, 'bill_50000' => 50000, 'bill_20000' => 20000, 'bill_10000' => 10000, 'bill_5000' => 5000, 'bill_2000' => 2000] as $f => $v) {
                    $totalBills += ($bills[$f] ?? 0) * $v;
                }
                $totalCoins = 0;
                foreach (['coin_1000' => 1000, 'coin_500' => 500, 'coin_200' => 200, 'coin_100' => 100, 'coin_50' => 50] as $f => $v) {
                    $totalCoins += ($coins[$f] ?? 0) * $v;
                }

                $recordId = DB::table('machine_stocking_records')->insertGetId(array_merge(
                    [
                        'tenant_id'             => self::TENANT_ID,
                        'code'                  => $code,
                        'machine_id'            => $machineId,
                        'route_id'              => $routeId,
                        'vehicle_warehouse_id'  => $vehicleWhId,
                        'performed_by'          => $driver,
                        'status'                => 'completado',
                        'was_offline'           => false,
                        'notes'                 => null,
                        'started_at'            => $startedAt,
                        'loaded_at'             => $loadedAt,
                        'completed_at'          => $completedAt,
                        'total_cash_bills'      => $totalBills,
                        'total_cash_coins'      => $totalCoins,
                        'total_cash'            => $totalBills + $totalCoins,
                        'created_at'            => $startedAt,
                        'updated_at'            => $completedAt,
                    ],
                    $bills,
                    $coins
                ));

                // 4-8 productos por surtido
                $selectedProducts = collect($products)->shuffle()->take(rand(4, 8));
                foreach ($selectedProducts as $productId) {
                    $qtyLoaded   = rand(5, 25);
                    $countBefore = rand(0, 8);
                    DB::table('machine_stocking_items')->insert([
                        'stocking_record_id'    => $recordId,
                        'product_id'            => $productId,
                        'quantity_loaded'       => $qtyLoaded,
                        'physical_count_before' => $countBefore,
                        'physical_count_after'  => $countBefore + $qtyLoaded,
                        'difference'            => $qtyLoaded,
                        'notes'                 => null,
                    ]);
                }

                $stockingCount++;
            }
        }

        $this->command->info("  → {$stockingCount} surtidos históricos completados.");
    }
}
