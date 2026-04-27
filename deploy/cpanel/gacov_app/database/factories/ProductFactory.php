<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $categories = ['bebida_fria', 'bebida_caliente', 'snack', 'insumo', 'otro'];
        $units = ['Und.', 'Kg', 'Lt', 'Caja', 'Paquete', 'Bolsa'];

        return [
            'code' => strtoupper($this->faker->unique()->lexify('???')).$this->faker->numberBetween(100, 999),
            'worldoffice_code' => $this->faker->optional()->numerify('WO-#####'),
            'name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement($categories),
            'unit_of_measure' => $this->faker->randomElement($units),
            'cost' => $this->faker->randomFloat(2, 200, 40000),
            'min_sale_price' => $this->faker->randomFloat(2, 250, 50000),
            'unit_price' => $this->faker->randomFloat(2, 500, 50000),
            'min_stock_alert' => $this->faker->numberBetween(5, 50),
            'supplier' => $this->faker->optional()->company(),
            'supplier_sku' => $this->faker->optional()->bothify('SUP-###??'),
            'expiration_date' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
            'purchase_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function withMinStock(int $minStock): static
    {
        return $this->state(fn (array $attributes): array => [
            'min_stock_alert' => $minStock,
        ]);
    }

    public function ofCategory(string $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => $category,
        ]);
    }
}
