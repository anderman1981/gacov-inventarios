@extends('layouts.app')

@section('title', 'Inventario de vehículos')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Inventario movil</span>
            <h1 class="inventory-hero__title">Vehiculos por ruta</h1>
            <p class="inventory-hero__subtitle">Cada ruta funciona como una bodega movil con inventario inicial, stock actual y disponibilidad para la operacion diaria.</p>
            <div class="inventory-hero__badges">
                <span class="badge badge-info">Vehiculos activos: {{ number_format($totalRoutes, 0, ',', '.') }}</span>
                <span class="badge badge-success">Con bodega: {{ number_format($configuredVehicles, 0, ',', '.') }}</span>
                <span class="badge badge-neutral">Unidades totales: {{ number_format($totalUnits, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</section>

<div class="kpi-grid" style="margin-bottom:var(--space-2)">
    <section class="kpi-card">
        <div class="kpi-label">Vehículos activos</div>
        <div class="kpi-value">{{ number_format($totalRoutes, 0, ',', '.') }}</div>
    </section>
    <section class="kpi-card">
        <div class="kpi-label">Bodegas configuradas</div>
        <div class="kpi-value">{{ number_format($configuredVehicles, 0, ',', '.') }}</div>
    </section>
    <section class="kpi-card">
        <div class="kpi-label">Unidades en vehículos</div>
        <div class="kpi-value">{{ number_format($totalUnits, 0, ',', '.') }}</div>
    </section>
</div>

<section class="inventory-filter-card">
    <div class="inventory-filter-card__header">
        <div>
            <div class="inventory-filter-card__title">Buscar vehiculo o ruta</div>
            <div class="inventory-filter-card__copy">Filtra por codigo o nombre de ruta para revisar rapidamente el inventario movil.</div>
        </div>
        <div class="badge badge-neutral">Rutas visibles: {{ number_format($routes->total(), 0, ',', '.') }}</div>
    </div>
    <div class="inventory-filter-card__body">
        <form method="GET" action="{{ route('inventory.vehicles') }}" class="inventory-filter-form">
            <div class="form-group" style="flex:1;min-width:220px;margin-bottom:0">
                <label class="form-label">Buscar ruta o código</label>
                <input type="text" name="search" class="form-input" placeholder="Ej. RT1 o Ruta 1" value="{{ request('search') }}">
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
                <a href="{{ route('inventory.vehicles') }}" class="inventory-filter-reset">Limpiar</a>
            </div>
        </form>
    </div>
</section>

<div class="inventory-results-bar">
    <span>Vehículos filtrados: <strong>{{ number_format($routes->total(), 0, ',', '.') }}</strong></span>
    <span>Unidades visibles en esta página: <strong>{{ number_format($visibleUnits, 0, ',', '.') }}</strong></span>
</div>

<div class="inventory-card-grid">
    @forelse($routes as $route)
        <section class="inventory-location-card">
            <div class="inventory-location-card__head">
                <div>
                    <div class="inventory-location-card__title">{{ $route->code }} — {{ $route->name }}</div>
                    <p class="inventory-location-card__subtitle">
                        Conductor: <strong>{{ $route->driver->name ?? 'Sin conductor asignado' }}</strong>
                    </p>
                </div>
                <div class="inventory-location-card__stats">
                    <span class="badge {{ $route->vehicle_warehouse ? 'badge-success' : 'badge-neutral' }}">
                        {{ $route->vehicle_warehouse ? 'Bodega configurada' : 'Sin bodega' }}
                    </span>
                    <span class="badge {{ $route->vehicle_stocks->isNotEmpty() ? 'badge-info' : 'badge-neutral' }}">
                        {{ $route->vehicle_stocks->isNotEmpty() ? 'Inventario cargado' : 'Sin inventario inicial' }}
                    </span>
                </div>
            </div>

            <div class="inventory-location-card__body">
                <p style="color:var(--gacov-text-secondary);margin-bottom:var(--space-4);">
                    Bodega vehículo:
                    <strong>{{ $route->vehicle_warehouse?->name ?? 'No configurada' }}</strong>
                </p>

                <div class="inventory-location-card__stats">
                    <span class="badge badge-info">SKUs con stock: {{ number_format($route->vehicle_stocks->count(), 0, ',', '.') }}</span>
                    <span class="badge {{ $route->vehicle_stocks->isNotEmpty() ? 'badge-success' : 'badge-neutral' }}">
                        Unidades: {{ number_format((int) $route->vehicle_stocks->sum('quantity'), 0, ',', '.') }}
                    </span>
                </div>

                @if($route->vehicle_stocks->isNotEmpty())
                    @php
                        $previewStocks = $route->vehicle_stocks->sortByDesc('quantity')->take(5);
                        $remainingStocks = max(0, $route->vehicle_stocks->count() - $previewStocks->count());
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
                        Vista rápida de 5 productos. Hay {{ number_format($remainingStocks, 0, ',', '.') }} productos más en esta ruta.
                    </div>
                    @endif
                @else
                    <div class="inventory-empty" style="padding:var(--space-6) 0">
                        <p class="inventory-empty__title">Sin stock en este vehiculo</p>
                        <p>No hay stock cargado para este vehículo.</p>
                    </div>
                @endif
            </div>
        </section>
    @empty
        <section class="panel">
            <div class="inventory-empty">
                No hay rutas activas configuradas.
            </div>
        </section>
    @endforelse
</div>
@if($routes->hasPages())
<div class="inventory-pagination">
    <div class="inventory-pagination__meta">
        Mostrando {{ $routes->firstItem() }}-{{ $routes->lastItem() }} de {{ $routes->total() }} rutas
    </div>
    <div>{{ $routes->links() }}</div>
</div>
@endif
</div>
@endsection
