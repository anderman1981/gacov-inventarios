<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
final class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'movement_type' => $this->faker->randomElement([
                'ajuste_manual', 'traslado', 'surtido_maquina', 'venta_maquina',
            ]),
            'origin_warehouse_id' => Warehouse::factory(),
            'destination_warehouse_id' => null,
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 50),
            'unit_cost' => 0,
            'reference_code' => null,
            'notes' => null,
            'performed_by' => User::factory(),
        ];
    }
}
