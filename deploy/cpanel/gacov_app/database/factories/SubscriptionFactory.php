<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
final class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'tenant_id'            => Tenant::factory(),
            'plan_id'              => SubscriptionPlan::factory(),
            'status'               => 'active',
            'billing_cycle'        => 'monthly',
            'current_period_start' => now(),
            'current_period_end'   => now()->addMonth(),
            'trial_ends_at'        => null,
        ];
    }

    public function trial(): static
    {
        return $this->state(fn (array $attrs): array => [
            'status'       => 'trial',
            'trial_ends_at'=> now()->addDays(14),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => 'suspended']);
    }
}
