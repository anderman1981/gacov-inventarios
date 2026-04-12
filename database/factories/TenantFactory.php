<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name'      => $this->faker->company(),
            'slug'      => $this->faker->unique()->slug(2),
            'nit'       => $this->faker->numerify('###.###.###-#'),
            'email'     => $this->faker->unique()->companyEmail(),
            'phone'     => $this->faker->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs): array => ['is_active' => false]);
    }
}
