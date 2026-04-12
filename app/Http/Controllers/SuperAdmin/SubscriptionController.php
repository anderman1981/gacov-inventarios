<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SubscriptionController extends Controller
{
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'status' => 'required|in:trial,active,suspended,cancelled',
        ]);

        $periodStart = now();
        $periodEnd = $data['billing_cycle'] === 'yearly' ? $periodStart->copy()->addYear() : $periodStart->copy()->addMonth();
        $subscription = $tenant->subscriptions()->latest()->first();

        if ($subscription) {
            $subscription->update([
                'current_period_end' => $periodStart,
                'cancelled_at' => $periodStart,
            ]);
        }

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $data['plan_id'],
            'billing_cycle' => $data['billing_cycle'],
            'status' => $data['status'],
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
        ]);

        return back()->with('success', 'Suscripción actualizada correctamente.');
    }
}
