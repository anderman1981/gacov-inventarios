@extends('super-admin.layout')

@section('title', 'Clientes')

@section('content')
<div class="page-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
    <div>
        <h1 class="page-title">Clientes</h1>
        <p class="page-subtitle">Gestión de todos los tenants del sistema</p>
    </div>
    <a href="{{ route('super-admin.tenants.create') }}" class="btn-primary">
        + Nuevo cliente
    </a>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:20px; padding:16px;">
    <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div style="flex:1; min-width:200px;">
            <label class="form-label">Buscar</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-input" placeholder="Nombre, NIT, email...">
        </div>
        <div>
            <label class="form-label">Estado</label>
            <select name="status" class="form-input" style="width:140px;">
                <option value="">Todos</option>
                <option value="active"    @selected(request('status') === 'active')>Activo</option>
                <option value="trial"     @selected(request('status') === 'trial')>Prueba</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Suspendido</option>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="padding:10px 20px;">Filtrar</button>
        @if(request()->hasAny(['q','status']))
            <a href="{{ route('super-admin.tenants.index') }}" class="btn-secondary" style="padding:10px 20px;">Limpiar</a>
        @endif
    </form>
</div>

{{-- Tabla --}}
<div class="card" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>NIT</th>
                <th>Plan</th>
                <th>Estado</th>
                <th>Usuarios</th>
                <th>Vence</th>
                <th>Creado</th>
                <th style="text-align:right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $tenant)
            <tr>
                <td>
                    <div style="font-weight:600; color:var(--amr-text-primary);">{{ $tenant->name }}</div>
                    <div style="font-size:11px; color:var(--amr-text-muted); font-family:var(--font-mono);">{{ $tenant->slug }}</div>
                    <div style="font-size:11px; color:var(--amr-text-muted);">{{ $tenant->email }}</div>
                </td>
                <td style="font-family:var(--font-mono); font-size:12px;">{{ $tenant->nit ?? '—' }}</td>
                <td>
                    @if($tenant->subscription?->plan)
                        <span class="badge-info">{{ $tenant->subscription->plan->name }}</span>
                    @else
                        <span style="color:var(--amr-text-muted); font-size:12px;">Sin plan</span>
                    @endif
                </td>
                <td>
                    @php $status = $tenant->subscription?->status ?? ($tenant->is_active ? 'active' : 'suspended'); @endphp
                    @if($status === 'active')
                        <span class="badge-success">Activo</span>
                    @elseif($status === 'trial')
                        <span class="badge-warning">Prueba</span>
                    @else
                        <span class="badge-error">Suspendido</span>
                    @endif
                </td>
                <td style="text-align:center;">{{ $tenant->users_count }}</td>
                <td style="font-size:12px; color:var(--amr-text-secondary);">
                    @if($tenant->subscription?->current_period_end)
                        {{ $tenant->subscription->current_period_end->format('d/m/Y') }}
                    @else
                        —
                    @endif
                </td>
                <td style="font-size:12px; color:var(--amr-text-muted);">
                    {{ $tenant->created_at->format('d/m/Y') }}
                </td>
                <td style="text-align:right;">
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="btn-ghost" style="font-size:12px;">Ver</a>
                        <a href="{{ route('super-admin.tenants.edit', $tenant) }}" class="btn-ghost" style="font-size:12px;">Editar</a>
                        @if($tenant->is_active)
                            <form method="POST" action="{{ route('super-admin.tenants.suspend', $tenant) }}" onsubmit="return confirm('¿Suspender cliente {{ $tenant->name }}?')">
                                @csrf
                                <button type="submit" class="btn-ghost" style="font-size:12px; color:var(--amr-error);">Suspender</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('super-admin.tenants.activate', $tenant) }}">
                                @csrf
                                <button type="submit" class="btn-ghost" style="font-size:12px; color:var(--amr-success);">Activar</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; padding:48px; color:var(--amr-text-muted);">
                    No se encontraron clientes.
                    <a href="{{ route('super-admin.tenants.create') }}" style="color:var(--amr-primary); margin-left:8px;">Crear el primero</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($tenants->hasPages())
<div style="margin-top:16px;">
    {{ $tenants->withQueryString()->links() }}
</div>
@endif
@endsection
