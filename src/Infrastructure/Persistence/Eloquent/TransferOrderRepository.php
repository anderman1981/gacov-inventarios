<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Contract\Repository\TransferOrderRepositoryInterface;
use App\Models\TransferOrder;

final class TransferOrderRepository implements TransferOrderRepositoryInterface
{
    public function countByStatus(string $status): int
    {
        return TransferOrder::query()
            ->where('status', $status)
            ->count();
    }
}
