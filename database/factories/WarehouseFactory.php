<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
final class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' '.$this->faker->randomElement(['Bodega', 'Almacén', 'Centro']),
            'code' => strtoupper($this->faker->unique()->lexify('???')).$this->faker->numberBetween(100, 999),
            'type' => 'bodega',
            'is_active' => true,
        ];
    }

    public function bodega(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'bodega',
        ]);
    }

    public function vehiculo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'vehiculo',
            'name' => 'Vehículo '.$this->faker->numberBetween(1, 50),
        ]);
    }

    public function maquina(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'maquina',
            'name' => 'Máquina '.$this->faker->numberBetween(1, 100),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
