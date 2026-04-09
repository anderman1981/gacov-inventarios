@extends('layouts.app')
@section('title', 'Máquinas')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <h1 class="page-title">Máquinas</h1>
        <p class="page-subtitle">{{ $machines->total() }} máquina(s) registradas</p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        @if(auth()->user()?->can('machines.create') || auth()->user()?->can('inventory.load_excel'))
        <a href="{{ route('machines.import.form') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
            Carga masiva
        </a>
        @endif
        @can('machines.create')
        <a href="{{ route('machines.create') }}" class="btn btn-primary" style="width:auto">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Nueva máquina
        </a>
        @endcan
    </div>
</div>

{{-- Filtros --}}
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6)">
        <form method="GET" action="{{ route('machines.index') }}" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-input" placeholder="Nombre o código..." value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label class="form-label">Ruta</label>
                <select name="route_id" class="form-input">
                    <option value="">Todas las rutas</option>
                    @foreach($routes as $route)
                    <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>
                        {{ $route->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="min-width:160px;margin-bottom:0">
                <label class="form-label">Estado</label>
                <select name="is_active" class="form-input">
                    <option value="">Todos</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activas</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
            <a href="{{ route('machines.index') }}" style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px">Limpiar</a>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="panel">
    @if($machines->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>WO Code</th>
                <th>Nombre</th>
                <th>Ubicación</th>
                <th>Ruta</th>
                <th style="text-align:right">Stock total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($machines as $machine)
            @php $totalStock = $stockTotals[$machine->id] ?? 0; @endphp
            <tr>
                <td>
                    <code style="font-size:12px;color:var(--gacov-primary)">{{ $machine->code }}</code>
                </td>
                <td style="color:var(--gacov-text-muted);font-size:13px">
                    {{ $machine->worldoffice_code ?? '—' }}
                </td>
                <td>
                    <a href="{{ route('machines.show', $machine) }}"
                       style="color:var(--gacov-text-primary);text-decoration:none;font-weight:600">
                        {{ $machine->name }}
                    </a>
                    @if($machine->type)
                    <span style="display:block;font-size:11px;color:var(--gacov-text-muted);margin-top:2px">{{ $machine->type }}</span>
                    @endif
                </td>
                <td style="color:var(--gacov-text-secondary);font-size:13px">
                    {{ $machine->location ?? '—' }}
                </td>
                <td>
                    @if($machine->route)
                    <span class="badge badge-info">{{ $machine->route->name }}</span>
                    @else
                    <span style="color:var(--gacov-text-muted)">Sin ruta</span>
                    @endif
                </td>
                <td style="text-align:right">
                    @php
                    $stockColor = match(true) {
                        $totalStock < 10  => 'var(--gacov-error)',
                        $totalStock < 30  => 'var(--gacov-warning)',
                        default           => 'var(--gacov-success)',
                    };
                    @endphp
                    <strong style="color:{{ $stockColor }}">{{ number_format((float) $totalStock, 0, ',', '.') }}</strong>
                </td>
                <td>
                    <span class="badge {{ $machine->is_active ? 'badge-success' : 'badge-neutral' }}">
                        {{ $machine->is_active ? 'Activa' : 'Inactiva' }}
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:var(--space-2);align-items:center;flex-wrap:wrap">
                        <a href="{{ route('machines.show', $machine) }}"
                           title="Ver detalle"
                           style="padding:4px 8px;background:rgba(0,212,255,.1);color:var(--gacov-primary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                            Ver
                        </a>
                        @can('machines.edit')
                        <a href="{{ route('machines.edit', $machine) }}"
                           title="Editar"
                           style="padding:4px 8px;background:rgba(124,58,237,.1);color:var(--gacov-secondary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('machines.toggle', $machine) }}" style="display:inline">
                            @csrf
                            <button type="submit"
                                title="{{ $machine->is_active ? 'Desactivar' : 'Activar' }}"
                                style="padding:4px 8px;background:{{ $machine->is_active ? 'rgba(245,158,11,.1)' : 'rgba(16,185,129,.1)' }};color:{{ $machine->is_active ? 'var(--gacov-warning)' : 'var(--gacov-success)' }};border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer">
                                {{ $machine->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{-- Paginación --}}
    @if($machines->hasPages())
    <div style="padding:var(--space-4) var(--space-6);border-top:1px solid var(--gacov-border)">
        {{ $machines->links() }}
    </div>
    @endif
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-12) 0;color:var(--gacov-text-muted)">
        <svg viewBox="0 0 20 20" fill="currentColor" width="40" height="40" style="margin:0 auto var(--space-4);opacity:.3"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
        <p style="font-size:15px;font-weight:500;margin-bottom:var(--space-2)">Sin máquinas</p>
        <p style="font-size:13px">No se encontraron máquinas con los filtros actuales.</p>
    </div>
    @endif
</div>
@endsection
