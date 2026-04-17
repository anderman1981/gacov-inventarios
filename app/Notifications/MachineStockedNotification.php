<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Machine;
use App\Models\MachineStockingRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada a Admin y Manager cuando un conductor completa el surtido de una máquina.
 */
final class MachineStockedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly MachineStockingRecord $record,
        private readonly Machine $machine,
        private readonly User $conductor,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'machine_stocked',
            'title'          => "Máquina surtida: {$this->machine->name}",
            'message'        => "El conductor {$this->conductor->name} completó el surtido de la máquina {$this->machine->name} ({$this->machine->code}).",
            'record_id'      => $this->record->id,
            'record_code'    => $this->record->code,
            'machine_id'     => $this->machine->id,
            'machine_name'   => $this->machine->name,
            'machine_code'   => $this->machine->code,
            'machine_location' => $this->machine->location,
            'conductor_id'   => $this->conductor->id,
            'conductor_name' => $this->conductor->name,
            'total_cash'     => $this->record->total_cash,
            'completed_at'   => $this->record->completed_at?->toIso8601String(),
        ];
    }
}
