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
        <div class="inventory-hero__actions">
            @can('inventory.load_vehicle_excel')
            <a href="{{ route('inventory.vehicles.import.form') }}" class="btn btn-primary">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 2a1 1 0 01.894.553l2 4A1 1 0 0112 8h-1v5.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414L9 13.586V8H8a1 1 0 01-.894-1.447l2-4A1 1 0 0110 2zM4 16a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                Carga masiva por Excel
            </a>
            @endcan
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
                    @can('inventory.adjust')
                    @if($route->vehicle_warehouse)
                    <a href="{{ route('inventory.adjust', ['warehouse_id' => $route->vehicle_warehouse->id]) }}" class="badge badge-warning" style="text-decoration:none">
                        {{ $route->vehicle_inventory_initialized ? 'Agregar mercancía' : 'Registrar carga inicial' }}
                    </a>
                    @endif
                    @endcan
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
                    <div class="vehicle-stocks-scroll" style="max-height: 280px; overflow-y: auto; border-radius: var(--radius-md); margin-top: var(--space-3);">
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
                            @foreach($route->vehicle_stocks->sortByDesc('quantity') as $stock)
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
