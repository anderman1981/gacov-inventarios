<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

final class TenantBillingProfile extends Model
{
    public const DEFAULT_TOTAL_PROJECT_VALUE = 18144000;

    public const DEFAULT_PHASE_VALUE = 3800000;

    public const PHASE_LABELS = [
        1 => 'Starter',
        2 => 'Básico',
        3 => 'Profesional',
        4 => 'Empresarial',
        5 => 'Enterprise',
    ];

    public const PHASE_OPERATIONAL_FEES = [
        1 => 290000,
        2 => 690000,
        3 => 890000,
        4 => 1200000,
        5 => 1500000,
    ];

    protected $fillable = [
        'tenant_id',
        'current_phase',
        'current_phase_value',
        'total_project_value',
        'minimum_commitment_months',
        'phase_started_at',
        'phase_commitment_ends_at',
        'review_notice_at',
        'proposal_reference',
        'notes',
    ];

    protected $casts = [
        'current_phase' => 'integer',
        'current_phase_value' => 'decimal:2',
        'total_project_value' => 'decimal:2',
        'minimum_commitment_months' => 'integer',
        'phase_started_at' => 'datetime',
        'phase_commitment_ends_at' => 'datetime',
        'review_notice_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function phaseLabel(): string
    {
        return self::PHASE_LABELS[$this->current_phase] ?? 'Fase personalizada';
    }

    public function operationalMonthlyFee(): int
    {
        return self::PHASE_OPERATIONAL_FEES[$this->current_phase] ?? 0;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultPayload(int $phase = 1, ?Carbon $phaseStartedAt = null): array
    {
        $phaseStartedAt ??= now()->startOfDay();
        $minimumCommitmentMonths = 3;
        $phaseCommitmentEndsAt = $phaseStartedAt->copy()->addMonthsNoOverflow($minimumCommitmentMonths);

        return [
            'current_phase' => $phase,
            'current_phase_value' => self::DEFAULT_PHASE_VALUE,
            'total_project_value' => self::DEFAULT_TOTAL_PROJECT_VALUE,
            'minimum_commitment_months' => $minimumCommitmentMonths,
            'phase_started_at' => $phaseStartedAt,
            'phase_commitment_ends_at' => $phaseCommitmentEndsAt,
            'review_notice_at' => $phaseCommitmentEndsAt->copy()->subDays(15),
        ];
    }
}
