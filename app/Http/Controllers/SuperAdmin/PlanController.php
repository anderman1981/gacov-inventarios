<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\View;

final class PlanController extends Controller
{
    public function __invoke(): View
    {
        $plans = SubscriptionPlan::query()
            ->withCount([
                'subscriptions as active_subscriptions_count' => static fn ($query) => $query->whereIn('status', ['active', 'trial']),
                'subscriptions as total_subscriptions_count',
            ])
            ->orderBy('sort_order')
            ->orderBy('phase')
            ->get();

        $stats = [
            'total_plans' => $plans->count(),
            'active_plans' => $plans->where('is_active', true)->count(),
            'phase_count' => $plans->pluck('phase')->unique()->count(),
            'mrr_catalog' => $plans->where('is_active', true)->sum(static fn (SubscriptionPlan $plan): float => (float) $plan->monthly_price),
        ];

        return view('super-admin.plans.index', compact('plans', 'stats'));
    }
}
