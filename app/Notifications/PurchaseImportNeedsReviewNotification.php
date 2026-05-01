<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PurchaseImportBatch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class PurchaseImportNeedsReviewNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $actor,
        private readonly PurchaseImportBatch $batch,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Compra CSV con filas por corregir',
            'message' => "{$this->actor->name} reportó la compra temporal #{$this->batch->id} con {$this->batch->error_rows} fila(s) por validar.",
            'batch_id' => $this->batch->id,
            'batch_status' => $this->batch->status,
            'error_rows' => (int) $this->batch->error_rows,
            'valid_rows' => (int) $this->batch->valid_rows,
            'total_rows' => (int) $this->batch->total_rows,
            'uploader_name' => $this->batch->uploader?->name,
            'actor_name' => $this->actor->name,
            'action_url' => route('inventory.purchases.show', $this->batch),
        ];
    }
}
