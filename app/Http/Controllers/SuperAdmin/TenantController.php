<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppModule;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\TenantPayment;
use App\Models\User;
use App\Support\Billing\TenantBillingSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TenantController extends Controller
{
    public function index(): View
    {
        $query = Tenant::with(['subscription.plan'])
            ->withCount('users')
            ->latest();

        // Límite de 100 chars y escape de metacaracteres LIKE (%, _, \)
        // para evitar búsquedas abusivas o consultas ineficientes
        $search = mb_substr(trim((string) request('q', '')), 0, 100);
        if ($search !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(static function ($builder) use ($escaped): void {
                $builder
                    ->where('name', 'like', "%{$escaped}%")
                    ->orWhere('slug', 'like', "%{$escaped}%")
                    ->orWhere('nit', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        $status = request('status');
        if ($status === 'active' || $status === 'trial') {
            $query->whereHas('subscription', static function ($builder) use ($status): void {
                $builder->where('status', $status);
            });
        }

        if ($status === 'suspended') {
            $query->where(static function ($builder): void {
                $builder
                    ->where('is_active', false)
                    ->orWhereHas('subscription', static function ($subscriptionQuery): void {
                        $subscriptionQuery->where('status', 'suspended');
                    });
            });
        }

        $tenants = $query->paginate(20);

        return view('super-admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();

        return view('super-admin.tenants.create', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:tenants,slug|regex:/^[a-z0-9-]+$/',
            'nit' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'status' => 'required|in:trial,active',
        ]);

        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'nit' => $data['nit'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);
        $periodEnd = $data['billing_cycle'] === 'yearly' ? now()->addYear() : now()->addMonth();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $data['status'],
            'billing_cycle' => $data['billing_cycle'],
            'current_period_start' => now(),
            'current_period_end' => $periodEnd,
            'trial_ends_at' => $data['status'] === 'trial' ? now()->addDays(14) : null,
        ]);

        TenantBillingProfile::create([
            'tenant_id' => $tenant->id,
            ...TenantBillingProfile::defaultPayload(),
        ]);

        return redirect()->route('super-admin.tenants.show', $tenant)
            ->with('success', "Cliente {$tenant->name} creado exitosamente.");
    }

    public function show(Tenant $tenant, TenantBillingSummary $billingSummary): View
    {
        $tenant->load([
            'subscription.plan',
            'users.roles',
            'moduleOverrides.module',
            'billingProfile',
            'payments.recordedBy',
        ]);

        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();
        $modules = AppModule::query()
            ->active()
            ->orderBy('phase_required')
            ->orderBy('sort_order')
            ->get();
        $currentPhase = $tenant->phase();

        $userCount = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();

        return view('super-admin.tenants.show', [
            'tenant' => $tenant,
            'plans' => $plans,
            'activeModules' => $modules
                ->filter(fn (AppModule $module): bool => $tenant->hasModuleAccess($module->key))
                ->values(),
            'nextPhaseModules' => $modules
                ->filter(fn (AppModule $module): bool => $module->phase_required === ($currentPhase + 1))
                ->values(),
            'userCount' => $userCount,
            'billingSummary' => $billingSummary->build($tenant),
            'paymentTypes' => TenantPayment::typeOptions(),
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();

        return view('super-admin.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:100|unique:tenants,slug,{$tenant->id}|regex:/^[a-z0-9-]+$/",
            'nit' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        $tenant->update($data);

        return redirect()->route('super-admin.tenants.show', $tenant)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => false]);
        $tenant->subscription?->update(['status' => 'suspended']);

        return back()->with('success', "Cliente {$tenant->name} suspendido.");
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => true]);

        if ($tenant->subscription?->status === 'suspended') {
            $periodEnd = $tenant->subscription->billing_cycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            $tenant->subscription->update([
                'status' => 'active',
                'current_period_end' => $periodEnd,
            ]);
        }

        return back()->with('success', "Cliente {$tenant->name} activado.");
    }
}
