@extends('super-admin.layout')

@section('title', $tenant->name)

@section('content')
<div class="page-header" style="margin-bottom:24px;">
    <a href="{{ route('super-admin.tenants.index') }}" style="color:var(--amr-text-muted); font-size:13px; display:inline-flex; align-items:center; gap:6px; margin-bottom:12px;">
        ← Volver a clientes
    </a>
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px;">
        <div>
            <h1 class="page-title">{{ $tenant->name }}</h1>
            <p class="page-subtitle" style="font-family:var(--font-mono);">{{ $tenant->slug }}</p>
        </div>
        <div style="display:flex; gap:10px; flex-shrink:0; align-items:center;">
            <a href="{{ route('super-admin.tenants.edit', $tenant) }}" class="btn-secondary">Editar</a>
            @if($tenant->is_active)
                <form method="POST" action="{{ route('super-admin.tenants.suspend', $tenant) }}"
                    onsubmit="return confirm('¿Suspender cliente {{ $tenant->name }}? Los usuarios no podrán acceder al sistema.')">
                    @csrf
                    <button type="submit" style="background:rgba(239,68,68,.1); color:var(--amr-error); border:1px solid rgba(239,68,68,.3); border-radius:8px; padding:8px 16px; font-size:13px; font-weight:500; cursor:pointer;">
                        Suspender
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('super-admin.tenants.activate', $tenant) }}">
                    @csrf
                    <button type="submit" style="background:rgba(16,185,129,.1); color:var(--amr-success); border:1px solid rgba(16,185,129,.3); border-radius:8px; padding:8px 16px; font-size:13px; font-weight:500; cursor:pointer;">
                        Activar
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div style="background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.3); border-radius:10px; padding:12px 16px; margin-bottom:20px; color:var(--amr-success); font-size:13px;">
        ✓ {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div style="background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); border-radius:10px; padding:12px 16px; margin-bottom:20px; color:var(--amr-error); font-size:13px;">
        <div style="font-weight:700; margin-bottom:6px;">No se pudo guardar el cambio.</div>
        <ul style="margin:0; padding-left:18px; display:flex; flex-direction:column; gap:4px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

    {{-- Info empresa --}}
    <div class="card" style="padding:24px;">
        <h3 style="font-size:14px; font-weight:600; color:var(--amr-primary); margin-bottom:16px;">Información de la empresa</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div>
                <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Nombre</div>
                <div style="font-size:14px; font-weight:600;">{{ $tenant->name }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">NIT</div>
                <div style="font-size:14px; font-family:var(--font-mono);">{{ $tenant->nit ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Email</div>
                <div style="font-size:14px;">{{ $tenant->email }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Teléfono</div>
                <div style="font-size:14px;">{{ $tenant->phone ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Creado</div>
                <div style="font-size:14px;">{{ $tenant->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- Suscripción --}}
    <div class="card" style="padding:24px;">
        <h3 style="font-size:14px; font-weight:600; color:var(--amr-primary); margin-bottom:16px;">Suscripción</h3>
        @if($tenant->subscription)
            @php $sub = $tenant->subscription; @endphp
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div>
                    <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Plan</div>
                    <div style="font-size:16px; font-weight:700; color:var(--amr-primary);">{{ $sub->plan->name ?? '—' }}</div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Estado</div>
                        @if($sub->status === 'active')
                            <span class="badge-success">Activo</span>
                        @elseif($sub->status === 'trial')
                            <span class="badge-warning">Prueba</span>
                        @else
                            <span class="badge-error">{{ ucfirst($sub->status) }}</span>
                        @endif
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Ciclo</div>
                        <div style="font-size:14px;">{{ $sub->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Fase operativa</div>
                        <div style="font-size:14px; font-weight:700; color:var(--amr-primary);">F{{ $tenant->phase() }}</div>
                        @if(($sub->plan?->phase ?? null) !== $tenant->phase())
                            <div style="font-size:11px; color:var(--amr-text-muted); margin-top:2px;">Plan comercial: F{{ $sub->plan?->phase }}</div>
                        @endif
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Período actual</div>
                        <div style="font-size:12px; font-family:var(--font-mono);">
                            {{ $sub->current_period_start->format('d/m/Y') }} →
                            {{ $sub->current_period_end->format('d/m/Y') }}
                        </div>
                    </div>
                    @if($sub->trial_ends_at)
                    <div>
                        <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Fin prueba</div>
                        <div style="font-size:12px; font-family:var(--font-mono); color:var(--amr-warning);">{{ $sub->trial_ends_at->format('d/m/Y') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Actualizar suscripción --}}
            <form method="POST" action="{{ route('super-admin.tenants.subscription.update', $tenant) }}" style="margin-top:20px; padding-top:16px; border-top:1px solid var(--amr-border);">
                @csrf @method('PUT')
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                    <select name="plan_id" class="form-input" style="font-size:12px;">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected($sub->plan_id == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                    <select name="billing_cycle" class="form-input" style="font-size:12px;">
                        <option value="monthly" @selected($sub->billing_cycle === 'monthly')>Mensual</option>
                        <option value="yearly" @selected($sub->billing_cycle === 'yearly')>Anual</option>
                    </select>
                </div>
                <div style="display:grid; grid-template-columns:1fr; gap:10px; margin-bottom:12px;">
                    <select name="status" class="form-input" style="font-size:12px;">
                        <option value="active"    @selected($sub->status === 'active')>Activo</option>
                        <option value="trial"     @selected($sub->status === 'trial')>Prueba</option>
                        <option value="suspended" @selected($sub->status === 'suspended')>Suspendido</option>
                        <option value="cancelled" @selected($sub->status === 'cancelled')>Cancelado</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="font-size:12px; padding:8px 16px;">Actualizar suscripción</button>
            </form>
        @else
            <p style="color:var(--amr-text-muted); font-size:13px;">Sin suscripción registrada.</p>
        @endif
    </div>

    @include('super-admin.tenants.partials.billing-panel', [
        'tenant' => $tenant,
        'billingSummary' => $billingSummary,
        'activeModules' => $activeModules,
        'nextPhaseModules' => $nextPhaseModules,
        'paymentTypes' => $paymentTypes,
    ])

    {{-- Usuarios --}}
    <div class="card" style="padding:24px; grid-column:1/-1;">
        <h3 style="font-size:14px; font-weight:600; color:var(--amr-primary); margin-bottom:16px;">
            Usuarios ({{ $userCount }})
        </h3>
        @if($tenant->users->count())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenant->users as $user)
                    <tr>
                        <td style="font-weight:500;">{{ $user->name }}</td>
                        <td style="font-size:12px; color:var(--amr-text-secondary);">{{ $user->email }}</td>
                        <td>
                            @foreach($user->roles as $role)
                                <span style="background:rgba(0,212,255,.1); color:var(--amr-primary); border-radius:4px; padding:2px 8px; font-size:11px; margin-right:4px;">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($user->is_active ?? true)
                                <span class="badge-success">Activo</span>
                            @else
                                <span class="badge-error">Inactivo</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--amr-text-muted);">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:var(--amr-text-muted); font-size:13px;">No hay usuarios registrados en este cliente.</p>
        @endif
    </div>

    {{-- Módulos override --}}
    @if($tenant->moduleOverrides->count())
    <div class="card" style="padding:24px; grid-column:1/-1;">
        <h3 style="font-size:14px; font-weight:600; color:var(--amr-primary); margin-bottom:16px;">Módulos con configuración especial</h3>
        <table class="data-table">
            <thead>
                <tr><th>Módulo</th><th>Clave</th><th>Habilitado</th></tr>
            </thead>
            <tbody>
                @foreach($tenant->moduleOverrides as $override)
                <tr>
                    <td>{{ $override->module->name ?? '—' }}</td>
                    <td style="font-family:var(--font-mono); font-size:12px;">{{ $override->module->key ?? '—' }}</td>
                    <td>
                        @if($override->is_enabled)
                            <span class="badge-success">✓ Habilitado</span>
                        @else
                            <span class="badge-error">✗ Deshabilitado</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
