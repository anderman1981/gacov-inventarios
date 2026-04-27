<?php

declare(strict_types=1);

namespace App\Contract\Repository;

interface TransferOrderRepositoryInterface
{
    public function countByStatus(string $status): int;
}
