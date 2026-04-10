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
            </div>
        </div>
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
            <div class="inventory-filter-card__title">Filtrar maquinas</div>
            <div class="inventory-filter-card__copy">Busca por codigo, nombre o ruta para revisar rapido el stock de cada maquina.</div>
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
                    <a href="{{ route('machines.show', $machine) }}" class="badge badge-info" style="text-decoration:none">
                        Ver detalle
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
                    @php
                        $previewStocks = $machine->machine_stocks->sortByDesc('quantity')->take(5);
                        $remainingStocks = max(0, $machine->machine_stocks->count() - $previewStocks->count());
                    @endphp
                    <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Código</th>
                                <th>Categoría</th>
                                <th>Disponible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewStocks as $stock)
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
                    @if($remainingStocks > 0)
                    <div class="inventory-panel-note" style="margin-top:0;border:1px solid #e9eff5;border-radius:16px">
                        Vista rápida de 5 productos. Hay {{ number_format($remainingStocks, 0, ',', '.') }} productos más en esta máquina.
                    </div>
                    @endif
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
