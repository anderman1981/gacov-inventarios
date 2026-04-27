<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Support\Billing\TenantBillingSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class TenantBillingController extends Controller
{
    public function updateProfile(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'current_phase' => 'required|integer|min:1|max:5',
            'current_phase_value' => 'required|numeric|min:0',
            'total_project_value' => 'required|numeric|min:0',
            'minimum_commitment_months' => 'required|integer|min:1|max:24',
            'phase_started_at' => 'nullable|date',
            'proposal_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $billingProfile = $tenant->billingProfile()->firstOrNew();
        $billingProfile->tenant_id = $tenant->id;
        $billingProfile->current_phase = (int) $data['current_phase'];
        $billingProfile->current_phase_value = (float) $data['current_phase_value'];
        $billingProfile->total_project_value = (float) $data['total_project_value'];
        $billingProfile->minimum_commitment_months = (int) $data['minimum_commitment_months'];
        $billingProfile->proposal_reference = $data['proposal_reference'] ?: null;
        $billingProfile->notes = $data['notes'] ?: null;
        $billingProfile->phase_started_at = filled($data['phase_started_at'])
            ? Carbon::parse($data['phase_started_at'])->startOfDay()
            : null;

        if ($billingProfile->phase_started_at !== null) {
            $billingProfile->phase_commitment_ends_at = $billingProfile->phase_started_at
                ->copy()
                ->addMonthsNoOverflow($billingProfile->minimum_commitment_months);

            $billingProfile->review_notice_at = $billingProfile->phase_commitment_ends_at
                ->copy()
                ->subDays(15);
        } else {
            $billingProfile->phase_commitment_ends_at = null;
            $billingProfile->review_notice_at = null;
        }

        $billingProfile->save();

        return back()->with('success', 'Control financiero de la fase actualizado.');
    }

    public function storePayment(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|in:development,operations,extra_hours,extraordinary',
            'phase' => 'nullable|integer|min:1|max:5',
            'description' => 'required|string|max:255',
            'invoice_number' => 'nullable|string|max:120',
            'amount' => 'required|numeric|min:0.01',
            'counts_toward_project_total' => 'nullable|boolean',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $subscriptionId = $tenant->subscription?->id;
        $countsTowardProjectTotal = $request->boolean('counts_toward_project_total');

        if ($data['type'] === 'development') {
            $countsTowardProjectTotal = true;
        }

        TenantPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscriptionId,
            'recorded_by_user_id' => auth()->id(),
            'type' => $data['type'],
            'phase' => isset($data['phase']) ? (int) $data['phase'] : null,
            'description' => $data['description'],
            'invoice_number' => $data['invoice_number'] ?: null,
            'amount' => (float) $data['amount'],
            'counts_toward_project_total' => $countsTowardProjectTotal,
            'paid_at' => Carbon::parse($data['paid_at'])->startOfDay(),
            'notes' => $data['notes'] ?: null,
        ]);

        return back()->with('success', 'Pago registrado en el control interno.');
    }

    public function report(Tenant $tenant, TenantBillingSummary $billingSummary): View
    {
        $tenant->load([
            'billingProfile',
            'subscription.plan',
            'payments.recordedBy',
        ]);

        $summary = $billingSummary->build($tenant);

        return view('super-admin.tenants.billing-report', [
            'tenant' => $tenant,
            'summary' => $summary,
        ]);
    }
}
