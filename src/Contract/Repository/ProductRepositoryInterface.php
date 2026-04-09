<?php

declare(strict_types=1);

namespace App\Contract\Repository;

interface ProductRepositoryInterface
{
    public function countActive(): int;
}
