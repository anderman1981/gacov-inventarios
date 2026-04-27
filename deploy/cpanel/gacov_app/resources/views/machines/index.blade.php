@extends('layouts.app')
@section('title', 'Máquinas')

@section('content')
<div class="machine-index-hero">
    <div class="machine-index-hero__copy">
        <h1 class="page-title">Máquinas</h1>
        <p class="page-subtitle">{{ $machines->total() }} máquina(s) registradas</p>
    </div>
    <div class="machine-index-hero__actions">
        @if(auth()->user()?->can('machines.create') || auth()->user()?->can('inventory.load_excel'))
        <a href="{{ route('machines.import.form') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V8l-5-5H4zm7 1.5V8h3.5L11 4.5zM5 11a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm0 3a1 1 0 011-1h5a1 1 0 110 2H6a1 1 0 01-1-1z"/></svg>
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
<div class="panel machine-index-panel machine-index-panel--filters">
    <div class="panel-body machine-index-panel__body">
        <form method="GET" action="{{ route('machines.index') }}" class="machine-index-filter-form">
            <div class="form-group machine-index-field machine-index-field--search">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-input" placeholder="Nombre o código..." value="{{ request('search') }}" oninput="clearTimeout(window.__machinesIndexSearchTimer); window.__machinesIndexSearchTimer = setTimeout(() => this.form.submit(), 350)">
            </div>
            <div class="form-group machine-index-field">
                <label class="form-label">Ruta</label>
                <select name="route_id" class="form-input" onchange="this.form.submit()">
                    <option value="">Todas las rutas</option>
                    @foreach($routes as $route)
                    <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>
                        {{ $route->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group machine-index-field">
                <label class="form-label">Estado</label>
                <select name="is_active" class="form-input" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activas</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>
            <div class="form-group machine-index-field machine-index-field--compact">
                <label class="form-label">Por página</label>
                <select name="per_page" class="form-input" onchange="this.form.submit()">
                    @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" {{ (int) request('per_page', 20) === $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="machine-index-filter-actions">
                <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
                <a href="{{ route('machines.index') }}" class="machine-index-filter-reset">Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="panel machines-table-panel">
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
                    <span class="machine-index-stock-total" style="--machine-index-stock-color: #0f172a">
                        {{ number_format((float) $totalStock, 0, ',', '.') }}
                    </span>
                </td>
                <td>
                    <span class="badge {{ $machine->is_active ? 'badge-success' : 'badge-neutral' }}">
                        {{ $machine->is_active ? 'Activa' : 'Inactiva' }}
                    </span>
                </td>
                <td>
                    <div class="machine-index-actions">
                        <a href="{{ route('machines.show', $machine) }}"
                           class="amr-icon-button amr-icon-button--primary amr-tooltip-trigger"
                           data-tooltip="Ver detalle"
                           aria-label="Ver detalle">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 3C6.5 3 3.46 5.23 2 8.5 3.46 11.77 6.5 14 10 14s6.54-2.23 8-5.5C16.54 5.23 13.5 3 10 3zm0 9a3.5 3.5 0 110-7 3.5 3.5 0 010 7zm0-5.5a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        </a>
                        @can('machines.edit')
                        <a href="{{ route('machines.edit', $machine) }}"
                           class="amr-icon-button amr-icon-button--warning amr-tooltip-trigger"
                           data-tooltip="Editar máquina"
                           aria-label="Editar máquina">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3a2 2 0 012.828 0l.586.586a2 2 0 010 2.828l-8.293 8.293a1 1 0 01-.465.263l-3.5.875a1 1 0 01-1.213-1.213l.875-3.5a1 1 0 01.263-.465L13.586 3zM12 5.414L7.5 9.914 7.086 11.5l1.586-.414L13.172 6l-1.172-1.172z"/></svg>
                        </a>
                        <form method="POST" action="{{ route('machines.toggle', $machine) }}" style="display:inline">
                            @csrf
                            <button type="submit"
                                class="amr-icon-button {{ $machine->is_active ? 'amr-icon-button--warning' : 'amr-icon-button--success' }} amr-tooltip-trigger"
                                data-tooltip="{{ $machine->is_active ? 'Desactivar máquina' : 'Activar máquina' }}"
                                aria-label="{{ $machine->is_active ? 'Desactivar máquina' : 'Activar máquina' }}">
                                @if($machine->is_active)
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16A8 8 0 0010 2zm0 3a1 1 0 011 1v3.17l1.59 1.58a1 1 0 11-1.42 1.42l-1.88-1.88A1 1 0 019 9V6a1 1 0 011-1z"/></svg>
                                @else
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16A8 8 0 0010 2zm0 3a1 1 0 011 1v3.17l1.59 1.58a1 1 0 11-1.42 1.42l-1.88-1.88A1 1 0 019 9V6a1 1 0 011-1z"/></svg>
                                @endif
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
    <div class="inventory-pagination">
        <div class="inventory-pagination__meta">
            Mostrando {{ $machines->firstItem() }}-{{ $machines->lastItem() }} de {{ $machines->total() }} máquinas
        </div>
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

@push('styles')
<style>
    .machine-index-filter-form .form-label {
        color: var(--gacov-text-primary);
        font-weight: 700;
    }

    .machine-index-filter-form .form-input {
        color: #0f172a;
        background: #fff;
        border-color: #cbd5e1;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }

    .machine-index-filter-form .form-input::placeholder {
        color: #94a3b8;
    }

    .machine-index-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--space-4);
        flex-wrap: wrap;
        margin-bottom: var(--space-6);
    }

    .machine-index-hero__actions {
        display: flex;
        gap: var(--space-3);
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .machine-index-panel--filters .panel-body {
        padding: var(--space-5) var(--space-6);
    }

    .machine-index-filter-form {
        display: grid;
        grid-template-columns: minmax(0, 2.2fr) minmax(0, 1fr) minmax(0, 1fr) minmax(120px, .7fr) auto;
        gap: var(--space-4);
        align-items: end;
    }

    .machine-index-field {
        min-width: 0;
        margin-bottom: 0;
    }

    .machine-index-field--search {
        min-width: 240px;
    }

    .machine-index-field--compact .form-input {
        min-width: 110px;
    }

    .machine-index-filter-actions {
        display: flex;
        gap: var(--space-3);
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .machine-index-filter-reset {
        padding: 11px 16px;
        color: var(--gacov-text-muted);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }

    .machines-table-panel .data-table td,
    .machines-table-panel .data-table th {
        color: var(--gacov-text-primary);
    }

    .machines-table-panel .data-table thead th {
        color: #334155;
        background: #f8fbff;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .machines-table-panel .data-table tbody td {
        color: #0f172a;
    }

    .machines-table-panel .data-table td:nth-child(2),
    .machines-table-panel .data-table td:nth-child(4) {
        color: var(--gacov-text-secondary);
    }

    .machines-table-panel .data-table td code {
        color: var(--gacov-primary);
        font-weight: 700;
    }

    .machines-table-panel .badge {
        font-weight: 700;
    }

    .machines-table-panel .badge-info {
        background: rgba(37, 99, 235, 0.14);
        color: #1d4ed8;
        border: 1px solid rgba(37, 99, 235, 0.22);
    }

    .machines-table-panel .badge-success {
        background: rgba(16, 185, 129, 0.18);
        color: #047857;
        border: 1px solid rgba(16, 185, 129, 0.22);
    }

    .machines-table-panel .badge-neutral {
        background: rgba(148, 163, 184, 0.12);
        color: #475569;
        border: 1px solid rgba(148, 163, 184, 0.20);
    }

    .machines-table-panel .data-table tbody tr:hover td {
        background: #f8fbff;
    }

    .machines-table-panel code {
        color: #ef4444;
        font-weight: 800;
    }

    .machines-table-panel strong {
        color: #0f172a;
    }

    .machine-index-stock-total {
        display: inline-block;
        color: var(--machine-index-stock-color);
        font-size: 15px;
        font-weight: 800;
        letter-spacing: 0;
    }

    .machine-index-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: nowrap;
    }

    .machines-table-panel .amr-icon-button {
        width: 34px;
        height: 34px;
    }

    .machines-table-panel .amr-icon-button svg {
        width: 15px;
        height: 15px;
    }

    @media (max-width: 1200px) {
        .machine-index-filter-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .machine-index-field--search {
            grid-column: 1 / -1;
        }

        .machine-index-filter-actions {
            grid-column: 1 / -1;
            justify-content: flex-start;
        }
    }

    @media (max-width: 720px) {
        .machine-index-hero__actions,
        .machine-index-filter-form {
            width: 100%;
        }

        .machine-index-filter-form {
            grid-template-columns: 1fr;
        }

        .machine-index-filter-actions {
            justify-content: stretch;
        }

        .machine-index-filter-actions .btn,
        .machine-index-filter-reset {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush
