<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TransferOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferOrder>
 */
final class TransferOrderFactory extends Factory
{
    protected $model = TransferOrder::class;

    public function definition(): array
    {
        return [
            'code' => 'TRAS-'.now()->format('Ymd').'-'.str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'origin_warehouse_id' => Warehouse::factory(),
            'destination_warehouse_id' => Warehouse::factory(),
            'status' => $this->faker->randomElement(['pendiente', 'aprobado', 'completado', 'cancelado', 'borrador']),
            'requested_by' => User::factory(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pendiente',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'aprobado',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completado',
            'approved_by' => User::factory(),
            'approved_at' => now()->subHour(),
            'completed_by' => User::factory(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelado',
        ]);
    }
}
