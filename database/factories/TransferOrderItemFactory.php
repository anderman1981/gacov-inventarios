<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\TransferOrder;
use App\Models\TransferOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferOrderItem>
 */
final class TransferOrderItemFactory extends Factory
{
    protected $model = TransferOrderItem::class;

    public function definition(): array
    {
        return [
            'transfer_order_id' => TransferOrder::factory(),
            'product_id' => Product::factory(),
            'quantity_requested' => $this->faker->numberBetween(1, 100),
            'quantity_dispatched' => null,
            'quantity_received' => null,
            'notes' => null,
        ];
    }

    public function dispatched(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity_dispatched' => $attributes['quantity_requested'],
        ]);
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity_received' => $attributes['quantity_dispatched'] ?? $attributes['quantity_requested'],
        ]);
    }
}
