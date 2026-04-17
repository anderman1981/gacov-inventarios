@extends('layouts.app')
@section('title', $machine->name)

@section('content')
{{-- Encabezado --}}
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-2)">
            <code class="machine-badge-code">{{ $machine->code }}</code>
            <span class="machine-status-pill {{ $machine->is_active ? 'machine-status-pill--active' : 'machine-status-pill--inactive' }}">
                {{ $machine->is_active ? 'Activa' : 'Inactiva' }}
            </span>
        </div>
        <h1 class="page-title">{{ $machine->name }}</h1>
        <p class="page-subtitle">
            <a href="{{ route('machines.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">Máquinas</a> / {{ $machine->name }}
        </p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        @can('machines.edit')
        <a href="{{ route('machines.edit', $machine) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
            Editar máquina
        </a>
        @endcan
        @can('stockings.create')
        <a href="{{ route('driver.stocking.create') }}?machine_id={{ $machine->id }}" class="btn btn-primary" style="width:auto">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg>
            Surtir esta máquina
        </a>
        @endcan
    </div>
</div>

{{-- Contenido principal: 2 columnas --}}
<div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:var(--space-6);margin-bottom:var(--space-6);align-items:stretch">

    {{-- Columna izquierda: Info general --}}
    <div class="panel" style="height:100%">
        <div class="panel-header">
            <span class="panel-title">Información general</span>
        </div>
        <div class="panel-body">
            <table class="machine-info-table">
                <tbody>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td class="machine-info-label">Código</td>
                        <td class="machine-info-value">
                            <code class="machine-info-code">{{ $machine->code }}</code>
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td class="machine-info-label">Código WO</td>
                        <td class="machine-info-value">{{ $machine->worldoffice_code ?? '—' }}</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td class="machine-info-label">Tipo</td>
                        <td class="machine-info-value">{{ $machine->type ?? '—' }}</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td class="machine-info-label">Ubicación</td>
                        <td class="machine-info-value">{{ $machine->location ?? '—' }}</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td class="machine-info-label">Ruta</td>
                        <td class="machine-info-value">
                            @if($machine->route)
                            <span class="machine-route-pill">{{ $machine->route->name }}</span>
                            @else
                            <span class="machine-info-muted">Sin ruta asignada</span>
                            @endif
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td class="machine-info-label">Operador</td>
                        <td class="machine-info-value">
                            {{ $machine->operator?->name ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="machine-info-label">Bodega</td>
                        <td class="machine-info-value">
                            @if($warehouse)
                            <code class="machine-info-code machine-info-code--muted">{{ $warehouse->code }}</code>
                            @else
                            <span class="machine-info-error">Sin bodega asociada</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Columna derecha: Stock actual --}}
    <div class="panel machine-stock-panel" style="height:100%;display:flex;flex-direction:column">
        <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-3);flex-wrap:wrap">
            <div>
                <span class="panel-title">Stock actual</span>
                <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:4px">
                    Filtra por producto, estado y cantidad por página.
                </div>
            </div>
            <span class="machine-stock-total">
                Total: <strong>{{ number_format((float) $stockSummaryTotal, 0, ',', '.') }}</strong> uds.
            </span>
        </div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:var(--space-4);flex:1;min-height:0">
            <form method="GET" action="{{ route('machines.show', $machine) }}" class="inventory-filter-form machine-stock-filters" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:var(--space-3);align-items:end">
                <div class="form-group" style="margin-bottom:0;min-width:0;grid-column:span 2">
                    <label class="form-label">Buscar stock</label>
                    <input type="search" name="stock_search" class="form-input" placeholder="Producto, código o SKU" value="{{ $stockSearch }}" oninput="clearTimeout(window.__machineStockSearchTimer); window.__machineStockSearchTimer = setTimeout(() => this.form.submit(), 350)">
                </div>
                <div class="form-group" style="margin-bottom:0;min-width:0">
                    <label class="form-label">Estado</label>
                    <select name="stock_filter" class="form-input" onchange="this.form.submit()">
                        <option value="all" {{ $stockFilter === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="with_stock" {{ $stockFilter === 'with_stock' ? 'selected' : '' }}>Con stock</option>
                        <option value="low_stock" {{ $stockFilter === 'low_stock' ? 'selected' : '' }}>Stock bajo</option>
                        <option value="empty" {{ $stockFilter === 'empty' ? 'selected' : '' }}>Sin stock</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;min-width:0">
                    <label class="form-label">Por página</label>
                    <select name="per_page" class="form-input" onchange="this.form.submit()">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:center">
                    <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
                    <a href="{{ route('machines.show', $machine) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
                        Limpiar
                    </a>
                    <span style="margin-left:auto;font-size:12px;color:var(--gacov-text-muted)">
                        {{ number_format($stockVisibleItems, 0, ',', '.') }} referencias visibles
                    </span>
                </div>
            </form>

            @if($stockItems->isNotEmpty())
            <div class="machine-stock-scroll" style="flex:1;min-height:0;overflow:auto;border-radius:var(--radius-md);border:1px solid var(--gacov-border)">
                <table class="data-table machine-stock-table" style="margin:0;table-layout:fixed;width:100%">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="width:16%">Código</th>
                            <th style="width:14%">SKU</th>
                            <th style="width:18%">Unidad</th>
                            <th style="width:14%;text-align:right">Cantidad</th>
                            <th style="width:18%">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockItems as $item)
                        <tr>
                            <td class="machine-stock-product">
                                <strong>{{ $item->product_name }}</strong>
                            </td>
                            <td class="machine-stock-muted">{{ $item->product_code }}</td>
                            <td class="machine-stock-muted">{{ $item->product_sku ?? '—' }}</td>
                            <td class="machine-stock-muted">{{ $item->product_unit }}</td>
                            <td class="machine-stock-qty {{ (float) $item->quantity <= 0 ? 'machine-stock-qty--empty' : ((float) $item->quantity <= 5 ? 'machine-stock-qty--low' : 'machine-stock-qty--ok') }}">
                                {{ number_format((float) $item->quantity, 0, ',', '.') }}
                            </td>
                            <td>
                                <span class="machine-stock-pill {{ $item->stock_state_class }}">{{ $item->stock_state_label }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($stockItems->hasPages())
            <div class="inventory-pagination">
                <div class="inventory-pagination__meta">
                    Mostrando {{ $stockItems->firstItem() }}-{{ $stockItems->lastItem() }} de {{ $stockItems->total() }} productos
                </div>
                <div>{{ $stockItems->links() }}</div>
            </div>
            @endif
            @else
            <div class="panel-body" style="text-align:center;padding:var(--space-8) 0;color:var(--gacov-text-muted);flex:1">
                <p style="font-size:13px">Sin stock registrado en esta máquina.</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Últimos surtidos --}}
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-header">
        <span class="panel-title">Últimos surtidos</span>
    </div>
    @if($recentStockings->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha</th>
                <th>Conductor</th>
                <th style="text-align:center">Items</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentStockings as $stocking)
            <tr>
                <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $stocking->code }}</code></td>
                <td style="color:var(--gacov-text-secondary);font-size:13px">
                    {{ $stocking->created_at->format('d/m/Y H:i') }}
                </td>
                <td style="font-size:13px">{{ $stocking->user?->name ?? '—' }}</td>
                <td style="text-align:center;font-weight:600">{{ $stocking->items_count }}</td>
                <td>
                    @php
                    $statusBadge = match($stocking->status) {
                        'completado' => 'badge-success',
                        'pendiente'  => 'badge-warning',
                        'cancelado'  => 'badge-error',
                        default      => 'badge-neutral',
                    };
                    @endphp
                    <span class="badge {{ $statusBadge }}">{{ ucfirst($stocking->status) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-8) 0;color:var(--gacov-text-muted)">
        <p style="font-size:13px">No hay surtidos registrados para esta máquina.</p>
    </div>
    @endif
</div>

{{-- Últimas ventas --}}
<div class="panel">
    <div class="panel-header">
        <span class="panel-title">Últimas ventas</span>
    </div>
    @if($recentSales->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha venta</th>
                <th>Registrado por</th>
                <th style="text-align:center">Uds. vendidas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentSales as $sale)
            <tr>
                <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $sale->code }}</code></td>
                <td style="color:var(--gacov-text-secondary);font-size:13px">
                    {{ $sale->sale_date?->format('d/m/Y') ?? $sale->created_at->format('d/m/Y') }}
                </td>
                <td style="font-size:13px">{{ $sale->user?->name ?? '—' }}</td>
                <td style="text-align:center;font-weight:600">{{ number_format((float) ($sale->total_units ?? 0), 0, ',', '.') }}</td>
                <td>
                    @php
                    $saleBadge = match($sale->status) {
                        'exportado'  => 'badge-success',
                        'pendiente'  => 'badge-warning',
                        'cancelado'  => 'badge-error',
                        default      => 'badge-neutral',
                    };
                    @endphp
                    <span class="badge {{ $saleBadge }}">{{ ucfirst($sale->status) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-8) 0;color:var(--gacov-text-muted)">
        <p style="font-size:13px">No hay ventas registradas para esta máquina.</p>
</div>
@endif
</div>
@endsection

@push('styles')
<style>
    .machine-stock-panel {
        min-height: 100%;
    }

    .machine-badge-code,
    .machine-info-code {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 999px;
        background: rgba(239, 68, 68, 0.08);
        color: #ef4444;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .machine-info-code--muted {
        background: rgba(14, 165, 233, 0.10);
        color: #0284c7;
    }

    .machine-status-pill,
    .machine-route-pill,
    .machine-stock-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .machine-status-pill--active {
        background: rgba(16, 185, 129, 0.18);
        color: #047857;
        border: 1px solid rgba(16, 185, 129, 0.22);
    }

    .machine-status-pill--inactive {
        background: rgba(148, 163, 184, 0.18);
        color: #475569;
        border: 1px solid rgba(148, 163, 184, 0.24);
    }

    .machine-route-pill {
        background: rgba(14, 165, 233, 0.14);
        color: #0369a1;
        border: 1px solid rgba(14, 165, 233, 0.18);
    }

    .machine-stock-pill.badge-success {
        background: rgba(16, 185, 129, 0.18);
        color: #047857;
        border: 1px solid rgba(16, 185, 129, 0.22);
    }

    .machine-stock-pill.badge-warning {
        background: rgba(245, 158, 11, 0.18);
        color: #b45309;
        border: 1px solid rgba(245, 158, 11, 0.22);
    }

    .machine-stock-pill.badge-error {
        background: rgba(239, 68, 68, 0.18);
        color: #b91c1c;
        border: 1px solid rgba(239, 68, 68, 0.22);
    }

    .machine-info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .machine-info-label {
        padding: var(--space-3) 0;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        width: 40%;
    }

    .machine-info-value {
        padding: var(--space-3) 0;
        color: #0f172a;
        font-weight: 600;
    }

    .machine-info-muted {
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .machine-info-error {
        color: #dc2626;
        font-size: 13px;
        font-weight: 700;
    }

    .machine-stock-total {
        font-size: 13px;
        color: #475569;
    }

    .machine-stock-total strong {
        color: #0f172a;
    }

    .machine-stock-table thead th {
        color: #475569;
        background: #f8fbff;
    }

    .machine-stock-table tbody td {
        color: #0f172a;
    }

    .machine-stock-product strong {
        color: #0f172a;
        display: block;
    }

    .machine-stock-muted {
        color: #64748b;
        font-size: 12px;
    }

    .machine-stock-qty {
        text-align: right;
        font-weight: 800;
        font-size: 15px;
    }

    .machine-stock-qty--ok { color: #047857; }
    .machine-stock-qty--low { color: #b45309; }
    .machine-stock-qty--empty { color: #dc2626; }

    .machine-stock-panel .panel-body {
        min-height: 0;
    }

    .machine-stock-table td,
    .machine-stock-table th {
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .machine-stock-table tbody tr:hover td {
        background: rgba(248, 250, 252, 0.9);
    }

    @media (max-width: 1100px) {
        .machine-stock-filters {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 720px) {
        .machine-stock-filters {
            grid-template-columns: 1fr !important;
        }

        .machine-stock-filters .form-group {
            grid-column: auto !important;
        }

        .machine-stock-scroll {
            max-height: 360px;
        }
    }
</style>
@endpush
