<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\TenantPayment;

final class TenantBillingSummary
{
    /**
     * @return array<string,mixed>
     */
    public function build(Tenant $tenant): array
    {
        $billingProfile = $tenant->billingProfile;
        $payments = $tenant->payments->sortByDesc(static fn (TenantPayment $payment): int => $payment->paid_at?->getTimestamp() ?? 0)->values();

        $currentPhase = $tenant->phase();
        $currentPhaseValue = (float) ($billingProfile?->current_phase_value ?? TenantBillingProfile::DEFAULT_PHASE_VALUE);
        $totalProjectValue = (float) ($billingProfile?->total_project_value ?? TenantBillingProfile::DEFAULT_TOTAL_PROJECT_VALUE);
        $operationalMonthlyFee = $billingProfile?->operationalMonthlyFee() ?? (TenantBillingProfile::PHASE_OPERATIONAL_FEES[$currentPhase] ?? 0);

        $paidTowardProjectTotal = (float) $payments
            ->filter(static fn (TenantPayment $payment): bool => $payment->counts_toward_project_total)
            ->sum('amount');

        $paidInCurrentPhase = (float) $payments
            ->filter(static fn (TenantPayment $payment): bool => $payment->counts_toward_project_total && $payment->phase === $currentPhase)
            ->sum('amount');

        $operationsPaid = (float) $payments
            ->filter(static fn (TenantPayment $payment): bool => $payment->type === 'operations')
            ->sum('amount');

        $extraPaid = (float) $payments
            ->filter(static fn (TenantPayment $payment): bool => in_array($payment->type, ['extra_hours', 'extraordinary'], true))
            ->sum('amount');

        $remainingProjectBalance = max($totalProjectValue - $paidTowardProjectTotal, 0);
        $remainingCurrentPhaseBalance = max($currentPhaseValue - $paidInCurrentPhase, 0);
        $invoiceCount = $payments
            ->filter(static fn (TenantPayment $payment): bool => filled($payment->invoice_number))
            ->count();

        return [
            'billing_profile' => $billingProfile,
            'payments' => $payments,
            'current_phase' => $currentPhase,
            'current_phase_label' => TenantBillingProfile::PHASE_LABELS[$currentPhase] ?? 'Fase personalizada',
            'current_phase_value' => $currentPhaseValue,
            'total_project_value' => $totalProjectValue,
            'minimum_commitment_months' => $billingProfile?->minimum_commitment_months ?? 3,
            'operational_monthly_fee' => $operationalMonthlyFee,
            'phase_started_at' => $billingProfile?->phase_started_at,
            'phase_commitment_ends_at' => $billingProfile?->phase_commitment_ends_at,
            'review_notice_at' => $billingProfile?->review_notice_at,
            'proposal_reference' => $billingProfile?->proposal_reference,
            'notes' => $billingProfile?->notes,
            'paid_toward_project_total' => $paidTowardProjectTotal,
            'paid_in_current_phase' => $paidInCurrentPhase,
            'operations_paid_total' => $operationsPaid,
            'extra_paid_total' => $extraPaid,
            'remaining_project_balance' => $remainingProjectBalance,
            'remaining_current_phase_balance' => $remainingCurrentPhaseBalance,
            'invoice_count' => $invoiceCount,
            'review_due' => $billingProfile?->review_notice_at?->isPast() ?? false,
            'upgrade_window_open' => $billingProfile?->phase_commitment_ends_at?->isPast() ?? false,
        ];
    }
}
