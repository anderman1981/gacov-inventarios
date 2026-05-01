<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Models\PurchaseImportBatch;
use App\Models\User;
use App\Notifications\PurchaseImportNeedsReviewNotification;
use App\Support\Inventory\InventoryAdjustmentService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class NotifyPurchaseImportIssuesHandler
{
    public function __construct(
        private readonly InventoryAdjustmentService $inventoryAdjustmentService,
    ) {}

    public function handle(PurchaseImportBatch $batch, User $actor): int
    {
        if ($batch->status !== 'borrador') {
            throw new RuntimeException('Solo se pueden notificar compras temporales en borrador.');
        }

        if ((int) $batch->error_rows <= 0) {
            throw new RuntimeException('No hay filas con error para notificar.');
        }

        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        $recipients = $this->inventoryAdjustmentService->adminRecipients($actor->id);

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send(
            $recipients,
            new PurchaseImportNeedsReviewNotification(
                actor: $actor,
                batch: $batch->loadMissing('uploader'),
            )
        );

        return $recipients->count();
    }
}
