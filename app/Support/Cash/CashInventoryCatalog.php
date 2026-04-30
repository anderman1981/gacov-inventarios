<?php

declare(strict_types=1);

namespace App\Support\Cash;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\Product;
use Illuminate\Support\Collection;

final class CashInventoryCatalog
{
    /**
     * @return array<int, array{code:string,name:string,category:string,unit_of_measure:string,cost:int,min_sale_price:int,unit_price:int,min_stock_alert:int}>
     */
    public static function definitions(): array
    {
        return [
            ['code' => 'CASH-B100000', 'name' => 'Billete $100.000', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 100000, 'min_sale_price' => 100000, 'unit_price' => 100000, 'min_stock_alert' => 10],
            ['code' => 'CASH-B50000', 'name' => 'Billete $50.000', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 50000, 'min_sale_price' => 50000, 'unit_price' => 50000, 'min_stock_alert' => 10],
            ['code' => 'CASH-B20000', 'name' => 'Billete $20.000', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 20000, 'min_sale_price' => 20000, 'unit_price' => 20000, 'min_stock_alert' => 15],
            ['code' => 'CASH-B10000', 'name' => 'Billete $10.000', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 10000, 'min_sale_price' => 10000, 'unit_price' => 10000, 'min_stock_alert' => 20],
            ['code' => 'CASH-B5000', 'name' => 'Billete $5.000', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 5000, 'min_sale_price' => 5000, 'unit_price' => 5000, 'min_stock_alert' => 25],
            ['code' => 'CASH-B2000', 'name' => 'Billete $2.000', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 2000, 'min_sale_price' => 2000, 'unit_price' => 2000, 'min_stock_alert' => 30],
            ['code' => 'CASH-B1000', 'name' => 'Billete $1.000', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 1000, 'min_sale_price' => 1000, 'unit_price' => 1000, 'min_stock_alert' => 40],
            ['code' => 'CASH-C1000', 'name' => 'Moneda $1.000', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 1000, 'min_sale_price' => 1000, 'unit_price' => 1000, 'min_stock_alert' => 40],
            ['code' => 'CASH-C500', 'name' => 'Moneda $500', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 500, 'min_sale_price' => 500, 'unit_price' => 500, 'min_stock_alert' => 50],
            ['code' => 'CASH-C200', 'name' => 'Moneda $200', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 200, 'min_sale_price' => 200, 'unit_price' => 200, 'min_stock_alert' => 80],
            ['code' => 'CASH-C100', 'name' => 'Moneda $100', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 100, 'min_sale_price' => 100, 'unit_price' => 100, 'min_stock_alert' => 100],
            ['code' => 'CASH-C50', 'name' => 'Moneda $50', 'category' => 'otro', 'unit_of_measure' => 'Und.', 'cost' => 50, 'min_sale_price' => 50, 'unit_price' => 50, 'min_stock_alert' => 120],
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    public function syncProducts(): Collection
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        foreach (self::definitions() as $definition) {
            $product = Product::withoutGlobalScopes()
                ->where('code', $definition['code'])
                ->first();

            if (! $product instanceof Product) {
                Product::query()->create([
                    'code' => $definition['code'],
                    'name' => $definition['name'],
                    'category' => $definition['category'],
                    'unit_of_measure' => $definition['unit_of_measure'],
                    'cost' => $definition['cost'],
                    'min_sale_price' => $definition['min_sale_price'],
                    'unit_price' => $definition['unit_price'],
                    'min_stock_alert' => $definition['min_stock_alert'],
                    'is_active' => true,
                ]);

                continue;
            }

            $updates = [
                'name' => $definition['name'],
                'category' => $definition['category'],
                'unit_of_measure' => $definition['unit_of_measure'],
                'cost' => $definition['cost'],
                'min_sale_price' => $definition['min_sale_price'],
                'unit_price' => $definition['unit_price'],
                'min_stock_alert' => $definition['min_stock_alert'],
                'is_active' => true,
            ];

            if ($product->tenant_id === null && $tenantId !== null) {
                $updates['tenant_id'] = $tenantId;
            }

            $product->forceFill($updates)->save();
        }

        return Product::query()
            ->where('code', 'like', 'CASH-%')
            ->orderByDesc('unit_price')
            ->orderBy('name')
            ->get();
    }
}
