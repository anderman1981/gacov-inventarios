@extends('super-admin.layout')

@section('title', 'Planes de suscripción')

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:var(--space-6);flex-wrap:wrap;">
    <div>
        <h1 class="page-title">Planes de suscripción</h1>
        <p class="page-subtitle">Catálogo comercial por fase, capacidad y módulos incluidos.</p>
    </div>
    <a href="{{ route('super-admin.tenants.index') }}" class="btn-secondary" style="text-decoration:none;">
        Ver clientes
    </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--space-4);margin-bottom:var(--space-6);">
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:28px;font-weight:700;color:var(--gacov-primary);font-family:var(--font-display);">{{ $stats['total_plans'] }}</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">Planes registrados</div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:28px;font-weight:700;color:var(--gacov-success);font-family:var(--font-display);">{{ $stats['active_plans'] }}</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">Planes activos</div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:28px;font-weight:700;color:var(--gacov-warning);font-family:var(--font-display);">{{ $stats['phase_count'] }}</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">Fases cubiertas</div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:28px;font-weight:700;color:var(--gacov-info);font-family:var(--font-display);">${{ number_format((float) $stats['mrr_catalog'], 0, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">MRR catálogo (COP)</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Oferta activa del sistema</h2>
    </div>
    <div class="panel-body" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Fase</th>
                        <th>Precio mensual</th>
                        <th>Precio anual</th>
                        <th>Capacidad</th>
                        <th>Módulos incluidos</th>
                        <th>Clientes</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>
                                <div style="font-weight:700;color:var(--gacov-text-primary);">{{ $plan->name }}</div>
                                <div style="font-size:11px;color:var(--gacov-text-muted);font-family:var(--font-mono);">{{ $plan->slug }}</div>
                                @if($plan->description)
                                    <div style="font-size:12px;color:var(--gacov-text-secondary);margin-top:4px;">{{ $plan->description }}</div>
                                @endif
                            </td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;background:rgba(59,130,246,.12);color:#60A5FA;border:1px solid rgba(96,165,250,.25);font-size:11px;font-weight:700;">
                                    Fase {{ $plan->phase }}
                                </span>
                            </td>
                            <td>${{ number_format((float) $plan->monthly_price, 0, ',', '.') }}</td>
                            <td>${{ number_format((float) $plan->yearly_price, 0, ',', '.') }}</td>
                            <td style="font-size:12px;color:var(--gacov-text-secondary);">
                                {{ $plan->max_users }} usuarios · {{ $plan->max_machines }} máquinas · {{ $plan->max_routes }} rutas
                            </td>
                            <td>
                                @php $modules = collect($plan->modules ?? []); @endphp
                                @if($modules->isNotEmpty())
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        @foreach($modules->take(4) as $moduleKey)
                                            <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;background:var(--gacov-bg-elevated);color:var(--gacov-text-secondary);font-size:11px;font-family:var(--font-mono);">{{ $moduleKey }}</span>
                                        @endforeach
                                        @if($modules->count() > 4)
                                            <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;background:rgba(0,212,255,.12);color:var(--gacov-primary);font-size:11px;">+{{ $modules->count() - 4 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:var(--gacov-text-muted);font-size:12px;">Sin módulos asignados</span>
                                @endif
                            </td>
                            <td>{{ $plan->active_subscriptions_count }} activos / {{ $plan->total_subscriptions_count }} total</td>
                            <td>
                                @if($plan->is_active)
                                    <span class="badge-success">Activo</span>
                                @else
                                    <span class="badge-error">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:48px;text-align:center;color:var(--gacov-text-muted);">
                                No hay planes registrados todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
