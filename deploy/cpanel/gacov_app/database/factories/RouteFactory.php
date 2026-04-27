<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Route>
 */
final class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'name'          => 'Ruta ' . $this->faker->city(),
            'code'          => strtoupper($this->faker->unique()->lexify('RUT-???')),
            'driver_user_id'=> null,
            'vehicle_plate' => strtoupper($this->faker->lexify('???-###')),
            'is_active'     => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs): array => ['is_active' => false]);
    }

    public function withDriver(int $userId): static
    {
        return $this->state(fn (array $attrs): array => ['driver_user_id' => $userId]);
    }
}
