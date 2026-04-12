<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Machine;
use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Machine>
 */
final class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        return [
            'code'              => strtoupper($this->faker->unique()->lexify('MAQ-???')),
            'worldoffice_code'  => $this->faker->optional()->numerify('WO-#####'),
            'name'              => 'Máquina ' . $this->faker->numberBetween(1, 999),
            'location'          => $this->faker->optional()->streetAddress(),
            'route_id'          => null,
            'operator_user_id'  => null,
            'type'              => $this->faker->randomElement(['vending_cafe', 'vending_snack', 'vending_bebida', 'mixta']),
            'is_active'         => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs): array => ['is_active' => false]);
    }

    public function forRoute(int $routeId): static
    {
        return $this->state(fn (array $attrs): array => ['route_id' => $routeId]);
    }

    public function withOperator(int $userId): static
    {
        return $this->state(fn (array $attrs): array => ['operator_user_id' => $userId]);
    }
}
