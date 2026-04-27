@extends('super-admin.layout')

@section('title', 'Panel de Control')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6)">
    <div>
        <h1 style="font-family:var(--font-display);font-size:24px;font-weight:700;color:var(--gacov-text-primary);margin-bottom:var(--space-1)">
            Panel de Control AMR Tech
        </h1>
        <p style="color:var(--gacov-text-muted);font-size:13px">
            Gestión global de clientes, planes y suscripciones
        </p>
    </div>
    <a href="{{ route('super-admin.tenants.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Nuevo Cliente
    </a>
</div>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--space-4);margin-bottom:var(--space-8)">

    {{-- Total Clientes --}}
    <div class="panel" style="text-align:center;padding:var(--space-6)">
        <div style="font-size:36px;font-weight:700;color:var(--gacov-info);font-family:var(--font-display);line-height:1">
            {{ $stats['total_tenants'] }}
        </div>
        <div style="color:var(--gacov-text-muted);font-size:12px;margin-top:var(--space-2);text-transform:uppercase;letter-spacing:.5px">
            Total Clientes
        </div>
    </div>

    {{-- Activos --}}
    <div class="panel" style="text-align:center;padding:var(--space-6)">
        <div style="font-size:36px;font-weight:700;color:var(--gacov-success);font-family:var(--font-display);line-height:1">
            {{ $stats['active_tenants'] }}
        </div>
        <div style="color:var(--gacov-text-muted);font-size:12px;margin-top:var(--space-2);text-transform:uppercase;letter-spacing:.5px">
            Activos
        </div>
    </div>

    {{-- En Trial --}}
    <div class="panel" style="text-align:center;padding:var(--space-6)">
        <div style="font-size:36px;font-weight:700;color:var(--gacov-warning);font-family:var(--font-display);line-height:1">
            {{ $stats['trial_tenants'] }}
        </div>
        <div style="color:var(--gacov-text-muted);font-size:12px;margin-top:var(--space-2);text-transform:uppercase;letter-spacing:.5px">
            En Trial
        </div>
    </div>

    {{-- Suspendidos --}}
    <div class="panel" style="text-align:center;padding:var(--space-6)">
        <div style="font-size:36px;font-weight:700;color:var(--gacov-error);font-family:var(--font-display);line-height:1">
            {{ $stats['suspended_tenants'] }}
        </div>
        <div style="color:var(--gacov-text-muted);font-size:12px;margin-top:var(--space-2);text-transform:uppercase;letter-spacing:.5px">
            Suspendidos
        </div>
    </div>

    {{-- MRR --}}
    <div class="panel" style="text-align:center;padding:var(--space-6);background:linear-gradient(135deg,rgba(0,212,255,.08) 0%,rgba(124,58,237,.08) 100%);border-color:rgba(0,212,255,.2)">
        <div style="font-size:28px;font-weight:700;color:var(--gacov-primary);font-family:var(--font-display);line-height:1">
            ${{ number_format((float) $stats['mrr'], 0, ',', '.') }}
        </div>
        <div style="color:var(--gacov-text-muted);font-size:12px;margin-top:var(--space-2);text-transform:uppercase;letter-spacing:.5px">
            MRR (COP)
        </div>
    </div>

</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-4);margin-bottom:var(--space-8)">
    <div class="panel" style="padding:var(--space-5);background:linear-gradient(135deg,rgba(16,185,129,.08) 0%,rgba(0,212,255,.04) 100%);border-color:rgba(16,185,129,.18)">
        <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.08em">Abonado a proyectos</div>
        <div style="font-size:28px;font-weight:700;color:var(--gacov-success);margin-top:var(--space-2)">
            ${{ number_format((float) $stats['project_paid_total'], 0, ',', '.') }}
        </div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-1)">Solo pagos que abonan al valor total</div>
    </div>
    <div class="panel" style="padding:var(--space-5);background:linear-gradient(135deg,rgba(245,158,11,.08) 0%,rgba(124,58,237,.04) 100%);border-color:rgba(245,158,11,.18)">
        <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.08em">Saldo consolidado</div>
        <div style="font-size:28px;font-weight:700;color:var(--gacov-warning);margin-top:var(--space-2)">
            ${{ number_format((float) $stats['project_balance_total'], 0, ',', '.') }}
        </div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-1)">Frente al valor total cargado en control interno</div>
    </div>
    <div class="panel" style="padding:var(--space-5);background:linear-gradient(135deg,rgba(124,58,237,.08) 0%,rgba(0,212,255,.04) 100%);border-color:rgba(124,58,237,.18)">
        <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.08em">Avisos por revisar</div>
        <div style="font-size:28px;font-weight:700;color:#A78BFA;margin-top:var(--space-2)">
            {{ $stats['reviews_due_count'] }}
        </div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-1)">Fases que ya piden revisar propuesta o plan de pagos</div>
    </div>
</div>

{{-- Recent Tenants --}}
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Clientes Recientes</h2>
        <a href="{{ route('super-admin.tenants.index') }}" style="font-size:13px;color:var(--gacov-primary);text-decoration:none">
            Ver todos →
        </a>
    </div>
    <div class="panel-body" style="padding:0">
        @if($recentTenants->isEmpty())
        <div style="padding:var(--space-10);text-align:center;color:var(--gacov-text-muted)">
            No hay clientes registrados aún.
        </div>
        @else
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <th style="padding:var(--space-3) var(--space-4);text-align:left;color:var(--gacov-text-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Empresa</th>
                        <th style="padding:var(--space-3) var(--space-4);text-align:left;color:var(--gacov-text-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Slug</th>
                        <th style="padding:var(--space-3) var(--space-4);text-align:left;color:var(--gacov-text-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Plan</th>
                        <th style="padding:var(--space-3) var(--space-4);text-align:left;color:var(--gacov-text-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Fase</th>
                        <th style="padding:var(--space-3) var(--space-4);text-align:left;color:var(--gacov-text-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Estado</th>
                        <th style="padding:var(--space-3) var(--space-4);text-align:left;color:var(--gacov-text-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Creado</th>
                        <th style="padding:var(--space-3) var(--space-4);text-align:right;color:var(--gacov-text-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTenants as $tenant)
                    @php
                        $phase = $tenant->phase();
                        $phaseColors = [1=>'#6B7280',2=>'#3B82F6',3=>'#7C3AED',4=>'#F59E0B',5=>'#10B981'];
                        $phaseColor = $phaseColors[$phase] ?? '#6B7280';
                        $status = $tenant->subscription?->status ?? 'sin suscripción';
                        $statusBadge = match($status) {
                            'active'    => 'badge-success',
                            'trial'     => 'badge-warning',
                            'suspended' => 'badge-error',
                            'cancelled' => '',
                            default     => '',
                        };
                        $planPhase = $tenant->subscription?->plan?->phase;
                    @endphp
                    <tr style="border-bottom:1px solid var(--gacov-border);transition:background var(--transition)"
                        onmouseover="this.style.background='var(--gacov-bg-elevated)'"
                        onmouseout="this.style.background='transparent'">
                        <td style="padding:var(--space-3) var(--space-4);font-weight:600;color:var(--gacov-text-primary)">
                            {{ $tenant->name }}
                        </td>
                        <td style="padding:var(--space-3) var(--space-4)">
                            <code style="font-family:var(--font-mono);font-size:12px;color:var(--gacov-text-muted);background:var(--gacov-bg-elevated);padding:2px 6px;border-radius:var(--radius-sm)">{{ $tenant->slug }}</code>
                        </td>
                        <td style="padding:var(--space-3) var(--space-4);color:var(--gacov-text-secondary)">
                            {{ $tenant->subscription?->plan?->name ?? '—' }}
                        </td>
                        <td style="padding:var(--space-3) var(--space-4)">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:700;background:{{ $phaseColor }}22;color:{{ $phaseColor }};border:1px solid {{ $phaseColor }}44">
                                F{{ $phase }}
                            </span>
                            @if($planPhase !== null && $planPhase !== $phase)
                                <div style="font-size:10px;color:var(--gacov-text-muted);margin-top:4px;">Plan comercial: F{{ $planPhase }}</div>
                            @endif
                        </td>
                        <td style="padding:var(--space-3) var(--space-4)">
                            <span class="badge {{ $statusBadge }}" style="{{ $statusBadge ? '' : 'background:var(--gacov-bg-elevated);color:var(--gacov-text-muted)' }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td style="padding:var(--space-3) var(--space-4);color:var(--gacov-text-muted);font-size:12px">
                            {{ $tenant->created_at->format('d/m/Y') }}
                        </td>
                        <td style="padding:var(--space-3) var(--space-4);text-align:right">
                            <a href="{{ route('super-admin.tenants.show', $tenant) }}"
                               class="btn btn-sm"
                               style="text-decoration:none">
                                Ver
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Quick actions --}}
<div class="panel" style="margin-top:var(--space-4)">
    <div class="panel-header">
        <h2 class="panel-title">Acciones rápidas</h2>
    </div>
    <div class="panel-body" style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        <a href="{{ route('super-admin.tenants.create') }}" class="btn btn-primary" style="text-decoration:none">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Nuevo Cliente
        </a>
        <a href="{{ route('super-admin.tenants.index') }}" class="btn" style="text-decoration:none">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
            Ver todos los clientes
        </a>
        <a href="{{ route('super-admin.plans.index') }}" class="btn" style="text-decoration:none">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 10l1.293-1.293zm2.586 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L11.586 10l-1.293-1.293a1 1 0 011.414-1.414z" clip-rule="evenodd"/></svg>
            Gestionar planes
        </a>
        <a href="{{ route('super-admin.project.index') }}" class="btn" style="text-decoration:none">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h4v-2H4V5h12v10h-4v2h4a2 2 0 002-2V5a2 2 0 00-2-2H4z"/><path d="M9 9a1 1 0 011-1h.01A1 1 0 0111 9v5a1 1 0 11-2 0V9zM10 6a1.25 1.25 0 100 2.5A1.25 1.25 0 0010 6z"/></svg>
            Ver centro de proyecto
        </a>
    </div>
</div>

@endsection
