<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\View;

final class SubscriptionPortalController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function expired(): View
    {
        return view('subscription.expired', [
            'tenant' => $this->tenantContext->getTenant(),
        ]);
    }

    public function upgrade(): View
    {
        $tenant = $this->tenantContext->getTenant();

        return view('subscription.upgrade', [
            'tenant' => $tenant,
            'plans' => SubscriptionPlan::active()->orderBy('phase')->get(),
            'currentPlan' => $tenant?->subscription?->plan,
            'currentPhase' => $tenant?->phase() ?? 1,
            'requiredModule' => session('module_required'),
            'requiredPhase' => session('module_required_phase'),
        ]);
    }
}
