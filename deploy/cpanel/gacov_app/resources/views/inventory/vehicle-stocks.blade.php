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
            @can('drivers.assign_routes')
            <button type="button" class="btn" style="background:#eaf1f7;color:#0f172a" onclick="document.getElementById('vehicle-create-modal').showModal()">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                Crear vehículo
            </button>
            @endcan
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
            <div class="inventory-filter-card__title" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                Buscar vehículo o ruta
                <button type="button" class="amr-icon-button amr-tooltip-trigger" data-tooltip="Filtra por código o nombre de ruta" aria-label="Ayuda del filtro">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <div class="inventory-filter-card__copy">Revisa rápidamente el inventario móvil sin abrir cada tarjeta.</div>
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
                    <a href="{{ route('inventory.adjust', ['warehouse_id' => $route->vehicle_warehouse->id]) }}" class="amr-icon-button amr-icon-button--warning amr-tooltip-trigger" data-tooltip="{{ $route->vehicle_inventory_initialized ? 'Agregar mercancía' : 'Registrar carga inicial' }}" aria-label="{{ $route->vehicle_inventory_initialized ? 'Agregar mercancía' : 'Registrar carga inicial' }}">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a1 1 0 01.894.553l2 4A1 1 0 0112 8h-1v5.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414L9 13.586V8H8a1 1 0 01-.894-1.447l2-4A1 1 0 0110 2zM4 16a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                    </a>
                    @endif
                    @endcan
                    @can('drivers.assign_routes')
                    <form method="POST" action="{{ route('inventory.vehicles.destroy', $route) }}" onsubmit="return confirm('¿Eliminar este vehículo/ruta? Se desactivará y liberará el conductor asignado.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="amr-icon-button amr-icon-button--danger amr-tooltip-trigger" data-tooltip="Eliminar vehículo" aria-label="Eliminar vehículo">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 2a2 2 0 00-2 2v1H4a1 1 0 100 2h.5l.8 9.2A2 2 0 007.3 18h5.4a2 2 0 001.99-1.8L15.5 7H16a1 1 0 100-2h-2V4a2 2 0 00-2-2H8zm2 3a1 1 0 10-2 0v1h2V5zm-2 4a1 1 0 012 0v5a1 1 0 11-2 0V9z" clip-rule="evenodd"/></svg>
                        </button>
                    </form>
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
@can('drivers.assign_routes')
<dialog id="vehicle-create-modal" class="audit-modal" onclick="if(event.target===this)this.close()">
    <div class="audit-modal__inner" style="max-width:640px">
        <div class="audit-modal__header">
            <div>
                <div class="audit-modal__eyebrow">OPERACIÓN</div>
                <h2 class="audit-modal__title">Crear vehículo</h2>
                <p class="audit-modal__sub">Crea la ruta, asigna placa opcional y configura automáticamente su bodega móvil.</p>
            </div>
            <button type="button" class="audit-modal__close" onclick="document.getElementById('vehicle-create-modal').close()">✕</button>
        </div>

        <form method="POST" action="{{ route('inventory.vehicles.store') }}" style="display:grid;gap:var(--space-4);padding:var(--space-6)">
            @csrf
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="vehicle_name">Nombre de ruta</label>
                <input id="vehicle_name" type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="Ej. Ruta Norte" required>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="vehicle_code">Código</label>
                <input id="vehicle_code" type="text" name="code" class="form-input" value="{{ old('code') }}" placeholder="Ej. RT3" required>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="vehicle_plate">Placa</label>
                <input id="vehicle_plate" type="text" name="vehicle_plate" class="form-input" value="{{ old('vehicle_plate') }}" placeholder="Ej. ABC123">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="vehicle_driver">Conductor asignado</label>
                <select id="vehicle_driver" name="driver_user_id" class="form-input">
                    <option value="">Sin asignar</option>
                    @foreach($conductors as $conductor)
                    <option value="{{ $conductor->id }}" {{ (string) old('driver_user_id') === (string) $conductor->id ? 'selected' : '' }}>{{ $conductor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:var(--space-3)">
                <button type="button" class="btn" style="background:#eaf1f7;color:#0f172a" onclick="document.getElementById('vehicle-create-modal').close()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar vehículo</button>
            </div>
        </form>
    </div>
</dialog>
@endcan
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
