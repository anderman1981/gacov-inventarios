<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\TenantPayment;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'trial_tenants' => Subscription::where('status', 'trial')->count(),
            'suspended_tenants' => Tenant::where('is_active', false)->count(),
            'mrr' => Subscription::active()
                ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.billing_cycle', 'monthly')
                ->sum('subscription_plans.monthly_price'),
            'project_value_total' => TenantBillingProfile::sum('total_project_value'),
            'project_paid_total' => TenantPayment::where('counts_toward_project_total', true)->sum('amount'),
            'reviews_due_count' => TenantBillingProfile::query()
                ->whereNotNull('review_notice_at')
                ->where('review_notice_at', '<=', now())
                ->count(),
        ];

        $stats['project_balance_total'] = max(
            (float) $stats['project_value_total'] - (float) $stats['project_paid_total'],
            0
        );

        $recentTenants = Tenant::with(['subscription.plan', 'billingProfile'])->latest()->limit(10)->get();

        return view('super-admin.dashboard', compact('stats', 'recentTenants'));
    }
}
