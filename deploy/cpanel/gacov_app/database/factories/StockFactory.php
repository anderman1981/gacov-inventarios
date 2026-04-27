<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
final class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(0, 1000),
            'min_quantity' => $this->faker->numberBetween(5, 20),
        ];
    }

    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity' => $quantity,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity' => $this->faker->numberBetween(0, 5),
        ]);
    }

    public function forWarehouse(Warehouse $warehouse): static
    {
        return $this->state(fn (array $attributes): array => [
            'warehouse_id' => $warehouse->id,
        ]);
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->id,
        ]);
    }
}
