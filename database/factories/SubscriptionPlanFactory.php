<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
final class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $phase = $this->faker->numberBetween(1, 5);
        $monthlyPrice = $this->faker->randomFloat(2, 50000, 500000);

        return [
            'name'          => $this->faker->randomElement(['Starter', 'Pro', 'Enterprise']),
            'slug'          => $this->faker->unique()->slug(1),
            'phase'         => $phase,
            'monthly_price' => $monthlyPrice,
            'yearly_price'  => round($monthlyPrice * 12 * 0.85, 2),
            'features'      => [],
            'modules'       => [],
            'is_active'     => true,
            'sort_order'    => $this->faker->numberBetween(1, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs): array => ['is_active' => false]);
    }
}
