@extends('layouts.app')

@section('title', 'Inventario de máquinas')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Inventario por maquina</span>
            <h1 class="inventory-hero__title">Bodegas por maquina</h1>
            <p class="inventory-hero__subtitle">Cada maquina funciona como una bodega independiente con inventario inicial, stock actual y seguimiento por ruta.</p>
            <div class="inventory-hero__badges">
                <span class="badge badge-info">Maquinas activas: {{ number_format($totalMachines, 0, ',', '.') }}</span>
                <span class="badge badge-success">Con bodega: {{ number_format($configuredWarehouses, 0, ',', '.') }}</span>
                <span class="badge badge-neutral">Unidades totales: {{ number_format($totalUnits, 0, ',', '.') }}</span>
                <span class="badge {{ $machineBulkInitialAvailable ? 'badge-warning' : 'badge-neutral' }}">Pendientes por carga inicial: {{ number_format($machinesPendingInitialLoad, 0, ',', '.') }}</span>
            </div>
        </div>
        @can('inventory.load_machine_excel')
        <div class="inventory-hero__actions">
            @if($machineBulkInitialAvailable)
            <a href="{{ route('inventory.machines.import.form') }}" class="btn btn-primary">Carga inicial por Excel</a>
            @else
            <span class="btn" style="width:auto;background:#e5e7eb;color:#475569;cursor:not-allowed;opacity:.9">
                Carga masiva cerrada
            </span>
            @endif
        </div>
        @endcan
    </div>
</section>

<div class="kpi-grid" style="margin-bottom:var(--space-2)">
    <section class="kpi-card">
        <div class="kpi-label">Máquinas activas</div>
        <div class="kpi-value">{{ number_format($totalMachines, 0, ',', '.') }}</div>
    </section>
    <section class="kpi-card">
        <div class="kpi-label">Bodegas de máquina</div>
        <div class="kpi-value">{{ number_format($configuredWarehouses, 0, ',', '.') }}</div>
    </section>
    <section class="kpi-card">
        <div class="kpi-label">Unidades en máquinas</div>
        <div class="kpi-value">{{ number_format($totalUnits, 0, ',', '.') }}</div>
    </section>
</div>

<section class="inventory-filter-card">
    <div class="inventory-filter-card__header">
        <div>
            <div class="inventory-filter-card__title" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                Filtrar máquinas
                <button type="button" class="amr-icon-button amr-tooltip-trigger" data-tooltip="Busca por código, nombre o ruta" aria-label="Ayuda del filtro">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <div class="inventory-filter-card__copy">Consulta el stock de cada máquina sin abrir el detalle.</div>
        </div>
        <div class="badge badge-neutral">Maquinas visibles: {{ number_format($machines->total(), 0, ',', '.') }}</div>
    </div>
    <div class="inventory-filter-card__body">
        <form method="GET" action="{{ route('inventory.machines') }}" class="inventory-filter-form">
            <div class="form-group" style="flex:1;min-width:220px;margin-bottom:0">
                <label class="form-label">Buscar máquina o código</label>
                <input type="text" name="search" class="form-input" placeholder="Ej. M104 o Máquina 104" value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:220px;margin-bottom:0">
                <label class="form-label">Ruta</label>
                <select name="route_id" class="form-input">
                    <option value="">Todas las rutas</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ (string) request('route_id') === (string) $route->id ? 'selected' : '' }}>
                            {{ $route->code }} — {{ $route->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="min-width:130px;margin-bottom:0">
                <label class="form-label">Por página</label>
                <select name="per_page" class="form-input">
                    @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inventory-filter-actions">
                <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
                <a href="{{ route('inventory.machines') }}" class="inventory-filter-reset">Limpiar</a>
            </div>
        </form>
    </div>
</section>

<div class="inventory-results-bar">
    <span>Máquinas filtradas: <strong>{{ number_format($machines->total(), 0, ',', '.') }}</strong></span>
    <span>Unidades visibles en esta página: <strong>{{ number_format($visibleUnits, 0, ',', '.') }}</strong></span>
</div>

<div class="inventory-card-grid">
    @forelse($machines as $machine)
        <section class="inventory-location-card">
            <div class="inventory-location-card__head">
                <div>
                    <div class="inventory-location-card__title">{{ $machine->code }} — {{ $machine->name }}</div>
                    <p class="inventory-location-card__subtitle">
                        Ruta: <strong>{{ $machine->route?->name ?? 'Sin ruta asignada' }}</strong>
                    </p>
                </div>
                <div class="inventory-location-card__stats">
                    <span class="badge {{ $machine->machine_warehouse ? 'badge-success' : 'badge-neutral' }}">
                        {{ $machine->machine_warehouse ? 'Bodega configurada' : 'Sin bodega' }}
                    </span>
                    @can('inventory.adjust')
                    @if($machine->machine_warehouse)
                    <a href="{{ route('inventory.adjust', ['warehouse_id' => $machine->machine_warehouse->id]) }}" class="amr-icon-button amr-icon-button--warning amr-tooltip-trigger" data-tooltip="{{ $machine->has_initial_inventory ? 'Corregir inventario' : 'Carga inicial' }}" aria-label="{{ $machine->has_initial_inventory ? 'Corregir inventario' : 'Carga inicial' }}">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a1 1 0 01.894.553l2 4A1 1 0 0112 8h-1v5.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414L9 13.586V8H8a1 1 0 01-.894-1.447l2-4A1 1 0 0110 2zM4 16a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                    </a>
                    @endif
                    @endcan
                    <a href="{{ route('machines.show', $machine) }}" class="amr-icon-button amr-icon-button--primary amr-tooltip-trigger" data-tooltip="Ver detalle" aria-label="Ver detalle">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 3c-4.418 0-8 3.582-8 7s3.582 7 8 7 8-3.582 8-7-3.582-7-8-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/></svg>
                    </a>
                </div>
            </div>

            <div class="inventory-location-card__body">
                <div class="inventory-location-card__stats">
                    <span class="badge badge-info">SKUs con stock: {{ number_format($machine->stock_skus, 0, ',', '.') }}</span>
                    <span class="badge {{ $machine->stock_units > 0 ? 'badge-success' : 'badge-neutral' }}">
                        Unidades: {{ number_format($machine->stock_units, 0, ',', '.') }}
                    </span>
                    <span class="badge {{ $machine->machine_stocks->isNotEmpty() ? 'badge-success' : 'badge-neutral' }}">
                        {{ $machine->machine_stocks->isNotEmpty() ? 'Inventario cargado' : 'Sin inventario inicial' }}
                    </span>
                </div>

                <p style="color:var(--gacov-text-secondary);margin-bottom:var(--space-4);">
                    Bodega máquina:
                    <strong>{{ $machine->machine_warehouse?->name ?? 'No configurada' }}</strong>
                </p>

                @if($machine->machine_stocks->isNotEmpty())
                    <div class="machine-stocks-scroll" style="max-height: 280px; overflow-y: auto; border-radius: var(--radius-md); margin-top: var(--space-3);">
                    <table class="data-table" style="margin: 0;">
                        <thead style="position: sticky; top: 0; background: var(--gacov-bg-surface); z-index: 1;">
                            <tr>
                                <th>Producto</th>
                                <th>Código</th>
                                <th>Categoría</th>
                                <th>Disponible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($machine->machine_stocks->sortByDesc('quantity') as $stock)
                                <tr>
                                    <td>
                                        <div class="inventory-table-product">
                                            <span class="inventory-table-product__name">{{ $stock->product->name ?? '—' }}</span>
                                            <span class="inventory-table-product__meta">{{ $stock->product->unit_of_measure ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $stock->product->code ?? '—' }}</td>
                                    <td><span class="badge badge-neutral">{{ $stock->product->category ?? '—' }}</span></td>
                                    <td><span class="inventory-quantity is-good">{{ number_format((int) $stock->quantity, 0, ',', '.') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <div class="inventory-empty" style="padding:var(--space-6) 0">
                        <p class="inventory-empty__title">Sin stock en esta maquina</p>
                        <p>No hay inventario cargado para esta máquina.</p>
                    </div>
                @endif
            </div>
        </section>
    @empty
        <section class="panel">
            <div class="inventory-empty">
                No hay máquinas activas con los filtros actuales.
            </div>
        </section>
    @endforelse
</div>
@if($machines->hasPages())
<div class="inventory-pagination">
    <div class="inventory-pagination__meta">
        Mostrando {{ $machines->firstItem() }}-{{ $machines->lastItem() }} de {{ $machines->total() }} máquinas
    </div>
    <div>{{ $machines->links() }}</div>
</div>
@endif
</div>
@endsection
