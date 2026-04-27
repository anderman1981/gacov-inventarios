<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Services\Workflow;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud formal de upgrade de fase.
 * Representa el flujo: cliente solicita → admin aprueba → se ejecuta upgrade.
 */
final class PhaseUpgradeRequest extends Model
{
    protected $table = 'phase_upgrade_requests';

    protected $fillable = [
        'tenant_id',
        'requested_by',
        'approved_by',
        'current_phase',
        'requested_phase',
        'approved_phase',
        'status',
        'notes',
        'admin_notes',
        'requested_at',
        'approved_at',
        'executed_at',
    ];

    protected $casts = [
        'current_phase' => 'integer',
        'requested_phase' => 'integer',
        'approved_phase' => 'integer',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    // ==================== ESTADOS ====================

    public const STATUS_PENDING = 'pending';           // Esperando aprobación

    public const STATUS_APPROVED = 'approved';         // Aprobada, lista para ejecutar

    public const STATUS_REJECTED = 'rejected';         // Rechazada

    public const STATUS_EXECUTED = 'executed';        // Ejecutada (upgrade completado)

    public const STATUS_CANCELLED = 'cancelled';      // Cancelada por el cliente

    // ==================== RELACIONES ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    // ==================== MÉTODOS ====================

    /**
     * Aprueba la solicitud y la marca como lista para ejecutar.
     */
    public function approve(int $approverId, ?int $approvedPhase = null, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_phase' => $approvedPhase ?? $this->requested_phase,
            'admin_notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    /**
     * Rechaza la solicitud.
     */
    public function reject(int $approverId, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approverId,
            'admin_notes' => $reason,
            'approved_at' => now(),
        ]);
    }

    /**
     * Cancela la solicitud (por el cliente).
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Marca como ejecutada después de completar el upgrade.
     */
    public function markExecuted(): void
    {
        $this->update([
            'status' => self::STATUS_EXECUTED,
            'executed_at' => now(),
        ]);
    }

    /**
     * Verifica si se puede aprobar.
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica si se puede ejecutar.
     */
    public function canBeExecuted(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
