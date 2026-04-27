<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Contract\Repository\ProductRepositoryInterface;
use App\Models\Product;

final class ProductRepository implements ProductRepositoryInterface
{
    public function countActive(): int
    {
        return Product::query()
            ->where('is_active', true)
            ->count();
    }
}
