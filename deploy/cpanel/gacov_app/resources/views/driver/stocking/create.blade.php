@extends('layouts.app')
@section('title', 'Surtir máquina')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@section('content')
@php
    $currentUser = auth()->user();
    $canSeeCommercialColumns = (bool) ($currentUser?->isSuperAdmin() || $currentUser?->hasAnyRole(['admin', 'manager', 'contador']));
    $isConductor = (bool) ($currentUser?->hasRole('conductor'));
    $routeQuery = $route?->id ? ['route_id' => $route->id] : [];
    $selectedMachineId = (string) old('machine_id', request('machine_id', ''));
    $selectedMachine = $machines->firstWhere('id', (int) $selectedMachineId);
    $machineLocations = $machines
        ->map(fn ($machine) => filled($machine->location) ? trim((string) $machine->location) : 'Sin ubicación definida')
        ->unique(fn ($location) => mb_strtolower((string) $location))
        ->sort()
        ->values();
    $selectedLocationGroup = (string) old(
        'stocking_location_group',
        filled($selectedMachine?->location) ? trim((string) $selectedMachine->location) : ''
    );
    $productsCollection = $products->getCollection();
    $vehicleStockTotal = (int) $productsCollection->sum('vehicle_stock');
    $productsWithStock = (int) $productsCollection->filter(fn ($product) => (int) $product->vehicle_stock > 0)->count();
    $productsShown = $products->count();
    $productsTotal = $products->total();
@endphp
<div class="page-header">
    <h1 class="page-title">Surtir máquina</h1>
    <p class="page-subtitle"><a href="{{ route('driver.dashboard', $routeQuery) }}" style="color:var(--gacov-text-muted);text-decoration:none">Mi ruta</a> / Surtir máquina</p>
</div>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:var(--space-5)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div>
        <strong>No pudimos registrar el surtido.</strong>
        <ul style="margin-top:6px;padding-left:18px;list-style:disc;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@if($availableRoutes->isNotEmpty())
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body">
        <div class="stocking-selector-row">
            <form method="GET" action="{{ route('driver.stocking.create') }}" class="stocking-selector-card" id="stocking-context-form">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="driver-stocking-route-id">Seleccionar ruta</label>
                    <select id="driver-stocking-route-id" name="route_id" class="form-input" onchange="this.form.submit()">
                        <option value="">Seleccionar ruta...</option>
                        @foreach($availableRoutes as $availableRoute)
                        <option value="{{ $availableRoute->id }}" {{ $route?->id === $availableRoute->id ? 'selected' : '' }}>
                            {{ $availableRoute->code }} — {{ $availableRoute->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="machine_id" id="stocking-context-machine-id" value="{{ $selectedMachineId }}">
                <input type="hidden" name="stocking_location_group" id="stocking-context-location-group" value="{{ $selectedLocationGroup }}">
            </form>

            <div class="stocking-selector-card">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="stocking-location-group">Ubicación de la máquina <span style="color:var(--gacov-error)">*</span></label>
                    <select id="stocking-location-group" class="form-input" {{ $machines->isEmpty() ? 'disabled' : '' }}>
                        <option value="">Seleccionar ubicación...</option>
                        @foreach($machineLocations as $locationGroup)
                        @php
                            $locationMachineCount = $machines->filter(function ($machine) use ($locationGroup) {
                                $machineLocation = filled($machine->location) ? trim((string) $machine->location) : 'Sin ubicación definida';

                                return mb_strtolower($machineLocation) === mb_strtolower((string) $locationGroup);
                            })->count();
                        @endphp
                        <option value="{{ $locationGroup }}" {{ $selectedLocationGroup === (string) $locationGroup ? 'selected' : '' }}>
                            {{ $locationGroup }} ({{ $locationMachineCount }} máquina{{ $locationMachineCount === 1 ? '' : 's' }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="stocking-selector-card">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Máquina a surtir <span style="color:var(--gacov-error)">*</span></label>
                    <select
                        id="stocking-machine-id"
                        class="form-input {{ $errors->has('machine_id') ? 'is-invalid' : '' }}"
                        data-placeholder-default="Seleccionar ubicación primero..."
                        data-placeholder-ready="Seleccionar máquina..."
                        required
                        {{ $machines->isEmpty() ? 'disabled' : '' }}>
                        <option value="">Seleccionar máquina...</option>
                        @foreach($machines as $machine)
                        @php
                            $machineCode = strtoupper(trim((string) $machine->code));
                            $machineSelected = old('machine_id') == $machine->id || request('machine_id') == $machine->id;
                            $machineLocationGroup = filled($machine->location) ? trim((string) $machine->location) : 'Sin ubicación definida';
                        @endphp
                        <option
                            value="{{ $machine->id }}"
                            data-machine-option="true"
                            data-machine-code="{{ $machineCode }}"
                            data-machine-name="{{ $machine->name }}"
                            data-location-group="{{ $machineLocationGroup }}"
                            {{ $machineSelected ? 'selected' : '' }}>
                            {{ $machine->code }} — {{ $machine->name }}{{ $machine->location ? ' (' . $machineLocationGroup . ')' : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('machine_id')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div style="display:flex;gap:var(--space-3);flex-wrap:wrap;margin-bottom:var(--space-5)">
    <span class="badge badge-info">Ruta actual: {{ $route?->name ?? 'Sin ruta asignada' }}</span>
    @if($route?->code)
    <span class="badge badge-info">Código: {{ $route->code }}</span>
    @endif
    <span class="badge {{ $machines->isNotEmpty() ? 'badge-success' : 'badge-neutral' }}">
        Máquinas activas: {{ $machines->count() }}
    </span>
</div>

@if(!$vehicleWarehouse)
<div class="alert alert-error" style="margin-bottom:var(--space-6)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    Tu ruta no tiene bodega de vehículo configurada. Contacta al administrador.
</div>
@endif

<div class="panel stocking-panel">
    <div class="panel-header">
        <span class="panel-title">Registro de surtido</span>
        @if($vehicleWarehouse)
        <span class="badge badge-info">Vehículo: {{ $vehicleWarehouse->name }}</span>
        @endif
    </div>
    <div class="panel-body">
        <div class="stocking-summary-grid">
            <div class="stocking-summary-card">
                <div class="stocking-summary-label">Ruta actual</div>
                <div class="stocking-summary-value">{{ $route?->name ?? 'Sin ruta asignada' }}</div>
                <div class="stocking-summary-copy">{{ $route?->code ? 'Código ' . $route->code : 'Selecciona una ruta para continuar.' }}</div>
            </div>
            <div class="stocking-summary-card">
                <div class="stocking-summary-label">Máquina seleccionada</div>
                <div class="stocking-summary-value">{{ $selectedMachine?->name ?? 'Ninguna' }}</div>
                <div class="stocking-summary-copy">{{ $selectedMachine?->location ?? 'Escoge ubicación y máquina.' }}</div>
            </div>
            <div class="stocking-summary-card">
                <div class="stocking-summary-label">Stock visible en vehículo</div>
                <div class="stocking-summary-value">{{ number_format($vehicleStockTotal, 0, ',', '.') }}</div>
                <div class="stocking-summary-copy">{{ $productsWithStock }} productos con disponibilidad</div>
            </div>
            <div class="stocking-summary-card">
                <div class="stocking-summary-label">Perfil operativo</div>
                <div class="stocking-summary-value">{{ $canSeeCommercialColumns ? 'Lectura completa' : 'Modo conductor' }}</div>
                <div class="stocking-summary-copy">{{ $canSeeCommercialColumns ? 'Muestra costo, precio y stock exacto.' : 'Oculta valores sensibles y permite novedad.' }}</div>
            </div>
        </div>

        <div class="alert alert-info" style="margin-bottom:var(--space-5)">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-3a1 1 0 10-2 0 1 1 0 002 0zm-2 3a1 1 0 000 2v2a1 1 0 102 0v-2a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
            <div>
                <strong>Fase 1 — Inspección de máquina.</strong>
                Inspecciona la máquina e indica cuántas unidades de cada producto necesita. Después ve al vehículo a cargarlos.
            </div>
        </div>

        <form method="GET" action="{{ route('driver.stocking.create') }}" class="stocking-product-toolbar" id="stocking-product-filters" style="margin-bottom:var(--space-5)">
            <input type="hidden" name="route_id" value="{{ $route?->id }}">
            @if($selectedMachineId !== '')
            <input type="hidden" name="machine_id" value="{{ $selectedMachineId }}">
            @endif
            @if($selectedLocationGroup !== '')
            <input type="hidden" name="stocking_location_group" value="{{ $selectedLocationGroup }}">
            @endif
            <div class="form-group">
                <label class="form-label" for="stocking-product-search">Buscar producto</label>
                <input type="search" id="stocking-product-search" name="product_search" class="form-input" value="{{ $productSearch }}" placeholder="Código, nombre o categoría..." autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label" for="stocking-stock-filter">Filtro de stock</label>
                <select id="stocking-stock-filter" name="stock_filter" class="form-input">
                    <option value="all" @selected($stockFilter === 'all')>Todos</option>
                    <option value="with_stock" @selected($stockFilter === 'with_stock')>Con stock</option>
                    <option value="low_stock" @selected($stockFilter === 'low_stock')>Stock bajo</option>
                    <option value="empty" @selected($stockFilter === 'empty')>Sin stock</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="stocking-per-page">Por página</label>
                <select id="stocking-per-page" name="per_page" class="form-input">
                    @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="stocking-product-toolbar__meta">
                Mostrando {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} de {{ $productsTotal }} productos
            </div>
            <div class="stocking-product-toolbar__actions">
                <a href="{{ route('driver.stocking.create', ['route_id' => $route?->id, 'machine_id' => request('machine_id')]) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
                    Limpiar
                </a>
            </div>
        </form>

        <form method="POST" action="{{ route('driver.stocking.store') }}" class="stocking-form">
            @csrf
            <input type="hidden" name="route_id" value="{{ $route?->id }}">
            <input type="hidden" name="stocking_location_group" id="stocking-location-group-hidden" value="{{ $selectedLocationGroup }}">
            <input type="hidden" name="machine_id" id="stocking-machine-id-hidden" value="{{ old('machine_id', request('machine_id')) }}">

            <div style="margin-bottom:var(--space-6)">
                <p class="form-label" style="margin-bottom:var(--space-3)">Productos a surtir</p>
                <p style="font-size:12px;color:var(--gacov-text-muted);margin-bottom:var(--space-4)">
                    Ingresa las cantidades que necesita la máquina. Los productos sin disponibilidad en el vehículo aparecen bloqueados.
                </p>
                <div class="table-scroll stocking-table-scroll">
                <table class="data-table stocking-products-table">
                    <thead>
                        <tr>
                            <th style="width:24%">Producto</th>
                            <th style="width:9%">Unidad</th>
                            @if($canSeeCommercialColumns)
                            <th class="stocking-commercial-col" style="text-align:right;width:12%">Costo</th>
                            <th class="stocking-commercial-col" style="text-align:right;width:14%">Precio venta</th>
                            <th class="stocking-commercial-col" style="text-align:right;width:12%">Stock vehículo</th>
                            @else
                            <th style="width:28%">Novedad</th>
                            @endif
                            <th style="text-align:center;width:12%">Cantidad a surtir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        @php
                            $productCode = strtoupper(trim((string) $product->code));
                            $hasStock = (int) $product->vehicle_stock > 0;
                            $stockStatus = $hasStock ? ((int) $product->vehicle_stock <= 5 ? 'low_stock' : 'with_stock') : 'empty';
                            $defaultNovelty = old("items.{$product->id}.notes", '');
                        @endphp
                        <tr class="stocking-product-row{{ !$hasStock ? ' stocking-product-row--no-stock' : '' }}"
                            data-code="{{ $productCode }}"
                            data-search="{{ mb_strtolower(trim($product->name . ' ' . $productCode . ' ' . ($product->category ?? ''))) }}"
                            data-stock-status="{{ $stockStatus }}">
                            <td>
                                <strong>{{ $product->name }}</strong>
                                <div class="stocking-product-code">{{ $productCode }}</div>
                                @if(!$hasStock)
                                <span class="stocking-product-empty">
                                    ✕ Sin stock en vehículo
                                </span>
                                @endif
                            </td>
                            <td class="stocking-product-unit">{{ $product->unit }}</td>
                            @if($canSeeCommercialColumns)
                            <td class="stocking-commercial-cell">
                                <span class="stocking-commercial-value">{{ number_format((float) ($product->cost ?? 0), 0, ',', '.') }}</span>
                            </td>
                            <td class="stocking-commercial-cell">
                                <span class="stocking-commercial-value stocking-commercial-value--accent">{{ number_format((float) ($product->min_sale_price ?? 0), 0, ',', '.') }}</span>
                            </td>
                            <td class="stocking-commercial-cell">
                                <span class="stocking-stock-value {{ $hasStock ? ((int) $product->vehicle_stock <= 5 ? 'stocking-stock-value--low' : 'stocking-stock-value--ok') : 'stocking-stock-value--empty' }}">
                                    {{ number_format((float) $product->vehicle_stock, 0, ',', '.') }}
                                </span>
                            </td>
                            @else
                            <td>
                                <div class="stocking-novelty-cell">
                                    <button type="button" class="btn stocking-novelty-toggle" data-stocking-novelty-toggle data-target="#novelty-{{ $product->id }}">
                                        Registrar novedad
                                    </button>
                                    <span class="stocking-novelty-help">Daño, devolución, merma o vencimiento.</span>
                                    <textarea
                                        id="novelty-{{ $product->id }}"
                                        name="items[{{ $product->id }}][notes]"
                                        class="form-input stocking-novelty-input"
                                        rows="2"
                                        placeholder="Describe la novedad para este producto..."
                                        hidden>{{ $defaultNovelty }}</textarea>
                                </div>
                            </td>
                            @endif
                            <td>
                                @if($hasStock)
                                <input type="number"
                                       name="items[{{ $product->id }}][quantity]"
                                       value="{{ old("items.{$product->id}.quantity", 0) }}"
                                       min="0"
                                       step="1"
                                       data-product-name="{{ $product->name }}"
                                       data-available-stock="{{ (int) $product->vehicle_stock }}"
                                       data-quantity-input="true"
                                       class="form-input stocking-quantity-input"
                                       style="text-align:center">
                                @else
                                <input type="hidden" name="items[{{ $product->id }}][quantity]" value="0">
                                <span class="stocking-product-disabled">No disponible</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $canSeeCommercialColumns ? 5 : 4 }}">
                                <div class="stocking-table-empty" id="stocking-table-empty">
                                    No hay productos que coincidan con el filtro actual.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                @if($products->hasPages())
                <div class="stocking-pagination">
                    {{ $products->links() }}
                </div>
                @endif
            </div>

            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Notas opcionales sobre el surtido...">{{ old('notes') }}</textarea>
            </div>

            {{-- Geolocalización del reporte --}}
            <div class="form-group" style="background:var(--gacov-bg-elevated);padding:var(--space-4);border-radius:var(--radius-md);margin-bottom:var(--space-4)">
                <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-2)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" style="color:var(--gacov-primary)"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    <span class="form-label" style="margin-bottom:0">Ubicación GPS</span>
                    <span id="geolocation-status" class="badge badge-neutral" style="font-size:11px">Obteniendo...</span>
                </div>
                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                <input type="hidden" name="geolocation_accuracy" id="geolocation_accuracy" value="{{ old('geolocation_accuracy') }}">
                <p id="geolocation-info" style="font-size:12px;color:var(--gacov-text-muted);margin:0">
                    Cargando geolocalización...
                </p>
            </div>

            <div style="display:flex;gap:var(--space-3);padding-top:var(--space-4);border-top:1px solid var(--gacov-border)">
                <button type="submit" class="btn btn-primary" style="width:auto" {{ !$vehicleWarehouse ? 'disabled' : '' }}>
                    Continuar → Ir a cargar
                </button>
                <a href="{{ route('driver.dashboard', $routeQuery) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
                    Cancelar
                </a>
            </div>
        </form>

        @push('styles')
        <style>
            .stocking-panel {
                max-width: none;
            }

            .stocking-summary-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: var(--space-3);
                margin-bottom: var(--space-5);
            }

            .stocking-summary-card {
                border: 1px solid var(--gacov-border);
                border-radius: var(--radius-lg);
                background: #fff;
                padding: var(--space-4);
            }

            .stocking-summary-label {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: var(--gacov-text-muted);
                font-weight: 800;
                margin-bottom: 6px;
            }

            .stocking-summary-value {
                color: #0f172a;
                font-size: 16px;
                font-weight: 800;
                margin-bottom: 4px;
            }

            .stocking-summary-copy {
                color: #64748b;
                font-size: 12px;
                line-height: 1.4;
            }

            .stocking-form-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: var(--space-4);
                margin-bottom: var(--space-5);
            }

            .stocking-selector-row {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: var(--space-4);
            }

            .stocking-selector-card {
                padding: var(--space-4);
                border: 1px solid var(--gacov-border);
                border-radius: var(--radius-lg);
                background: #fff;
            }

            .stocking-product-toolbar {
                display: grid;
                grid-template-columns: minmax(0, 1.3fr) minmax(180px, .8fr) minmax(120px, .55fr) auto;
                gap: var(--space-4);
                align-items: end;
                margin-bottom: var(--space-4);
                padding: var(--space-4);
                border: 1px solid var(--gacov-border);
                border-radius: var(--radius-lg);
                background: #fbfdff;
            }

            .stocking-product-toolbar .form-group {
                margin-bottom: 0;
            }

            .stocking-product-toolbar__meta {
                color: #475569;
                font-size: 13px;
                font-weight: 700;
                white-space: nowrap;
                align-self: end;
                justify-self: end;
            }

            .stocking-product-toolbar__actions {
                justify-self: end;
            }

            .stocking-products-table {
                table-layout: fixed;
                width: 100%;
            }

            .stocking-products-table thead th {
                color: #475569;
                background: #f8fbff;
                font-weight: 800;
                position: sticky;
                top: 0;
                z-index: 2;
            }

            .stocking-products-table tbody td {
                color: #0f172a;
                vertical-align: top;
            }

            .stocking-product-code {
                font-size: 11px;
                color: #64748b;
                margin-top: 3px;
                font-weight: 700;
            }

            .stocking-product-empty {
                display: inline-flex;
                margin-top: 4px;
                color: #dc2626;
                font-size: 11px;
                font-weight: 700;
            }

            .stocking-product-unit {
                color: #475569;
                font-size: 13px;
            }

            .stocking-commercial-cell {
                text-align: right;
            }

            .stocking-commercial-value,
            .stocking-stock-value {
                display: inline-flex;
                align-items: center;
                justify-content: flex-end;
                width: 100%;
                font-size: 15px;
                font-weight: 800;
                color: #0f172a;
            }

            .stocking-commercial-value--accent {
                color: #1d4ed8;
            }

            .stocking-stock-value--ok {
                color: #047857;
            }

            .stocking-stock-value--low {
                color: #b45309;
            }

            .stocking-stock-value--empty {
                color: #dc2626;
            }

            .stocking-novelty-cell {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .stocking-novelty-toggle {
                width: fit-content;
                background: rgba(37, 99, 235, 0.10);
                color: #1d4ed8;
                border: 1px solid rgba(37, 99, 235, 0.18);
                padding: 8px 12px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 700;
            }

            .stocking-novelty-help {
                color: #64748b;
                font-size: 11px;
                line-height: 1.3;
            }

            .stocking-novelty-input {
                min-height: 72px;
                resize: vertical;
            }

            .stocking-quantity-input {
                max-width: 120px;
                margin: 0 auto;
            }

            .stocking-product-disabled {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                color: #dc2626;
                font-size: 12px;
                font-weight: 700;
            }

            .stocking-table-empty {
                margin-top: var(--space-4);
                padding: var(--space-4);
                border-radius: var(--radius-md);
                border: 1px dashed var(--gacov-border);
                color: #64748b;
                background: #fbfdff;
                font-size: 13px;
                text-align: center;
            }

            .stocking-table-scroll {
                max-height: min(72vh, 820px);
                overflow: auto;
                border: 1px solid var(--gacov-border);
                border-radius: var(--radius-lg);
                background: #fff;
            }

            .stocking-pagination {
                margin-top: var(--space-4);
                display: flex;
                justify-content: center;
            }

            .stocking-pagination .pagination {
                margin: 0;
            }

            @media (max-width: 1200px) {
                .stocking-summary-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .stocking-selector-row {
                    grid-template-columns: 1fr;
                }

                .stocking-product-toolbar {
                    grid-template-columns: 1fr;
                }

                .stocking-commercial-col {
                    display: none;
                }

                .stocking-product-toolbar__meta,
                .stocking-product-toolbar__actions {
                    justify-self: start;
                }
            }

            @media (max-width: 820px) {
                .stocking-form-grid {
                    grid-template-columns: 1fr;
                }

                .stocking-selector-row {
                    grid-template-columns: 1fr;
                }

                .stocking-summary-grid {
                    grid-template-columns: 1fr;
                }

                .stocking-products-table th,
                .stocking-products-table td {
                    padding-top: 10px;
                    padding-bottom: 10px;
                }

                .stocking-products-table .stocking-product-unit,
                .stocking-products-table .stocking-product-disabled {
                    font-size: 12px;
                }

                .stocking-table-scroll {
                    max-height: 64vh;
                }
            }
        </style>
        @endpush
    </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const locationSelect = document.getElementById('stocking-location-group');
    const machineSelect = document.getElementById('stocking-machine-id');
    const productRows = Array.from(document.querySelectorAll('.stocking-product-row'));
    const stockingForm = machineSelect?.closest('form');
    const contextForm = document.getElementById('stocking-context-form');
    const productFilterForm = document.getElementById('stocking-product-filters');
    const productSearchInput = document.getElementById('stocking-product-search');
    const productStatusFilter = document.getElementById('stocking-stock-filter');
    const perPageSelect = document.getElementById('stocking-per-page');
    const contextMachineInput = document.getElementById('stocking-context-machine-id');
    const contextLocationInput = document.getElementById('stocking-context-location-group');
    const locationHiddenInput = document.getElementById('stocking-location-group-hidden');
    const machineHiddenInput = document.getElementById('stocking-machine-id-hidden');
    let importedRows = [];
    const machineOptions = machineSelect
        ? Array.from(machineSelect.querySelectorAll('option[data-machine-option="true"]')).map((option) => ({
            value: option.value,
            label: option.textContent?.trim() ?? '',
            locationGroup: option.dataset.locationGroup ?? '',
            machineCode: option.dataset.machineCode ?? '',
            machineName: option.dataset.machineName ?? '',
        }))
        : [];

    function normalizeCode(code) {
        return String(code ?? '')
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');
    }

    function normalizeProductCode(code) {
        const normalized = normalizeCode(code);
        const digitsOnly = normalized.match(/^\d+$/);

        if (!digitsOnly) {
            return normalized;
        }

        return normalized.replace(/^0+/, '') || '0';
    }

    function normalizeMachineCode(code) {
        const normalized = normalizeCode(code);
        const digitsMatch = normalized.match(/\d+/);

        if (!digitsMatch) {
            return normalized;
        }

        return digitsMatch[0].replace(/^0+/, '') || '0';
    }

    function getSelectedMachineCode() {
        const selectedOption = machineSelect?.selectedOptions?.[0];

        return normalizeMachineCode(selectedOption?.dataset.machineCode ?? '');
    }

    function createMachineOption(optionConfig) {
        const option = new Option(optionConfig.label, optionConfig.value, false, false);
        option.dataset.machineOption = 'true';
        option.dataset.locationGroup = optionConfig.locationGroup;
        option.dataset.machineCode = optionConfig.machineCode;
        option.dataset.machineName = optionConfig.machineName;

        return option;
    }

    function refreshMachineOptions() {
        if (!(locationSelect instanceof HTMLSelectElement) || !(machineSelect instanceof HTMLSelectElement)) {
            return;
        }

        const selectedLocation = locationSelect.value;
        const previousMachineId = machineSelect.value;
        const filteredMachines = selectedLocation === ''
            ? []
            : machineOptions.filter((option) => option.locationGroup === selectedLocation);

        const placeholderLabel = selectedLocation === ''
            ? (machineSelect.dataset.placeholderDefault ?? 'Seleccionar ubicación primero...')
            : (filteredMachines.length > 0
                ? (machineSelect.dataset.placeholderReady ?? 'Seleccionar máquina...')
                : 'No hay máquinas activas en esta ubicación');

        machineSelect.innerHTML = '';
        machineSelect.appendChild(new Option(placeholderLabel, ''));

        filteredMachines.forEach((optionConfig) => {
            machineSelect.appendChild(createMachineOption(optionConfig));
        });

        const nextMachineId = filteredMachines.some((option) => option.value === previousMachineId)
            ? previousMachineId
            : (filteredMachines.length === 1 ? filteredMachines[0].value : '');

        machineSelect.disabled = selectedLocation === '' || filteredMachines.length === 0;
        machineSelect.value = nextMachineId;

        if (locationHiddenInput instanceof HTMLInputElement) {
            locationHiddenInput.value = selectedLocation;
        }

        if (machineHiddenInput instanceof HTMLInputElement) {
            machineHiddenInput.value = machineSelect.value;
        }
    }

    function buildProductMap() {
        const rowMap = new Map();

        productRows.forEach((row) => {
            rowMap.set(normalizeProductCode(row.dataset.code ?? ''), row);
        });

        return rowMap;
    }

    function submitProductFilters() {
        if (productFilterForm instanceof HTMLFormElement) {
            productFilterForm.submit();
        }
    }

    function syncContextInputs() {
        if (contextMachineInput instanceof HTMLInputElement && machineSelect instanceof HTMLSelectElement) {
            contextMachineInput.value = machineSelect.value;
        }

        if (contextLocationInput instanceof HTMLInputElement && locationSelect instanceof HTMLSelectElement) {
            contextLocationInput.value = locationSelect.value;
        }
    }

    function submitContextForm() {
        syncContextInputs();

        if (contextForm instanceof HTMLFormElement) {
            contextForm.submit();
        }
    }

    function clearImportedFeedback() {
        document.getElementById('driver-stocking-photo-feedback')?.remove();
    }

    function renderFeedback(message, type = 'success') {
        clearImportedFeedback();

        const feedback = document.createElement('div');
        feedback.id = 'driver-stocking-photo-feedback';
        feedback.className = type === 'success' ? 'alert alert-success' : 'alert alert-error';
        feedback.style.marginBottom = 'var(--space-5)';
        feedback.innerHTML = `<div><strong>${message}</strong></div>`;

        const target = document.querySelector('.page-header');
        target?.insertAdjacentElement('afterend', feedback);
    }

    function validateQuantityInput(input) {
        if (!(input instanceof HTMLInputElement)) {
            return true;
        }

        const availableStock = Number(input.dataset.availableStock ?? 0);
        const requestedQuantity = Number(input.value || 0);
        const productName = input.dataset.productName ?? 'este producto';
        const tableRow = input.closest('.stocking-product-row');

        if (requestedQuantity > availableStock) {
            input.setCustomValidity(`No puedes surtir más de ${availableStock} unidad(es) para ${productName}.`);
            tableRow?.classList.add('stocking-product-row--invalid');
            return false;
        }

        input.setCustomValidity('');
        tableRow?.classList.remove('stocking-product-row--invalid');
        return true;
    }

    function refreshQuantityValidation() {
        let invalidCount = 0;

        productRows.forEach((tableRow) => {
            const input = tableRow.querySelector('[data-quantity-input="true"]');

            if (!input) {
                return;
            }

            if (!validateQuantityInput(input)) {
                invalidCount += 1;
            }
        });

        return invalidCount;
    }

    function applyImportedRowsToSelectedMachine() {
        const selectedMachineCode = getSelectedMachineCode();

        if (selectedMachineCode === '' || importedRows.length === 0) {
            return;
        }

        const productMap = buildProductMap();
        let appliedCount = 0;
        let machineColumnFound = false;

        productMap.forEach((tableRow) => {
            const input = tableRow.querySelector('[data-quantity-input="true"]');

            if (!input) {
                return;
            }

            input.value = '0';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        importedRows.forEach((row) => {
            const quantity = Number(row.quantities?.[selectedMachineCode] ?? 0);

            if (Object.prototype.hasOwnProperty.call(row.quantities ?? {}, selectedMachineCode)) {
                machineColumnFound = true;
            }

            const tableRow = productMap.get(normalizeProductCode(row.catalogCode ?? row.code ?? ''));

            if (!tableRow) {
                return;
            }

            const input = tableRow.querySelector('[data-quantity-input="true"]');

            if (!input) {
                return;
            }

            input.value = String(Math.max(0, quantity));
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            validateQuantityInput(input);

            if (quantity > 0) {
                appliedCount += 1;
            }
        });

        if (!machineColumnFound) {
            const selectedOption = machineSelect.selectedOptions?.[0];
            renderFeedback(`La planilla no trae cantidades para la máquina ${selectedOption?.textContent?.trim() ?? selectedMachineCode}.`, 'error');
            return;
        }

        const invalidCount = refreshQuantityValidation();

        if (invalidCount > 0) {
            renderFeedback(`Se cargaron cantidades para la máquina seleccionada, pero ${invalidCount} producto(s) superan el disponible en el vehículo. Ajusta esos valores antes de registrar.`, 'error');
            return;
        }

        renderFeedback(`Se aplicaron ${appliedCount} producto(s) a la máquina seleccionada desde la planilla de la ruta.`);
    }

    machineSelect?.addEventListener('change', () => {
        if (machineHiddenInput instanceof HTMLInputElement) {
            machineHiddenInput.value = machineSelect.value;
        }
        syncContextInputs();
        submitContextForm();
        applyImportedRowsToSelectedMachine();
    });

    locationSelect?.addEventListener('change', () => {
        if (locationHiddenInput instanceof HTMLInputElement) {
            locationHiddenInput.value = locationSelect.value;
        }
        syncContextInputs();
        refreshMachineOptions();
    });

    productRows.forEach((tableRow) => {
        const input = tableRow.querySelector('[data-quantity-input="true"]');
        const noveltyToggle = tableRow.querySelector('[data-stocking-novelty-toggle]');
        const noveltyInput = tableRow.querySelector('.stocking-novelty-input');

        if (!(input instanceof HTMLInputElement)) {
            // No quantity input in this row, but novelty still can apply.
        }

        if (noveltyToggle instanceof HTMLButtonElement && noveltyInput instanceof HTMLTextAreaElement) {
            noveltyToggle.addEventListener('click', () => {
                const shouldReveal = noveltyInput.hidden;

                noveltyInput.hidden = !shouldReveal;
                noveltyToggle.setAttribute('aria-expanded', shouldReveal ? 'true' : 'false');

                if (shouldReveal) {
                    noveltyInput.focus();
                }
            });
        }

        if (input instanceof HTMLInputElement) {
            input.addEventListener('input', () => {
                validateQuantityInput(input);
            });

            input.addEventListener('change', () => {
                validateQuantityInput(input);
            });
        }
    });

    productSearchInput?.addEventListener('input', () => {
        clearTimeout(window.__stockingProductFilterTimer);
        window.__stockingProductFilterTimer = setTimeout(() => {
            submitProductFilters();
        }, 180);
    });

    productStatusFilter?.addEventListener('change', submitProductFilters);
    perPageSelect?.addEventListener('change', submitProductFilters);

    stockingForm?.addEventListener('submit', (event) => {
        const invalidCount = refreshQuantityValidation();

        if (invalidCount === 0) {
            return;
        }

        event.preventDefault();
        renderFeedback(`No se pudo registrar el surtido porque ${invalidCount} producto(s) tienen cantidades mayores al disponible en el vehículo.`, 'error');

        const firstInvalidInput = stockingForm.querySelector('.stocking-product-row--invalid [data-quantity-input="true"]');

        if (firstInvalidInput instanceof HTMLInputElement) {
            firstInvalidInput.reportValidity();
            firstInvalidInput.focus();
        }
    });

    window.addEventListener('driver-stocking-photo-imported', (event) => {
        importedRows = Array.isArray(event.detail?.rows) ? event.detail.rows : [];

        applyImportedRowsToSelectedMachine();
    });

    if (locationSelect instanceof HTMLSelectElement && machineSelect instanceof HTMLSelectElement) {
        if (!locationSelect.value && machineSelect.value) {
            const currentMachine = machineOptions.find((option) => option.value === machineSelect.value);

            if (currentMachine) {
                locationSelect.value = currentMachine.locationGroup;
            }
        }

        if (!locationSelect.value && machineOptions.length === 1) {
            locationSelect.value = machineOptions[0].locationGroup;
        }

        refreshMachineOptions();
        syncContextInputs();
    }

    // Geolocalización del reporte
    const geolocationStatus = document.getElementById('geolocation-status');
    const geolocationInfo = document.getElementById('geolocation-info');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const accuracyInput = document.getElementById('geolocation_accuracy');

    function updateGeolocationStatus(status, message) {
        if (!geolocationStatus) return;

        geolocationStatus.textContent = status;
        switch (status) {
            case 'success':
                geolocationStatus.className = 'badge badge-success';
                break;
            case 'error':
                geolocationStatus.className = 'badge badge-error';
                break;
            default:
                geolocationStatus.className = 'badge badge-neutral';
        }

        if (geolocationInfo) {
            geolocationInfo.textContent = message;
        }
    }

    function handleGeolocationError(error) {
        console.warn('Geolocation error:', error);

        switch (error.code) {
            case error.PERMISSION_DENIED:
                updateGeolocationStatus('error', 'Permiso de ubicación denegado. Permite el acceso en tu navegador para registrar la posición.');
                break;
            case error.POSITION_UNAVAILABLE:
                updateGeolocationStatus('error', 'Ubicación no disponible. El GPS del dispositivo no funciona.');
                break;
            case error.TIMEOUT:
                updateGeolocationStatus('error', 'Timeout al obtener ubicación. Intenta de nuevo.');
                break;
            default:
                updateGeolocationStatus('error', 'Error desconocido al obtener ubicación.');
        }
    }

    function handleGeolocationSuccess(position) {
        const lat = position.coords.latitude.toFixed(6);
        const lng = position.coords.longitude.toFixed(6);
        const accuracy = Math.round(position.coords.accuracy);

        if (latitudeInput) latitudeInput.value = lat;
        if (longitudeInput) longitudeInput.value = lng;
        if (accuracyInput) accuracyInput.value = accuracy + 'm';

        updateGeolocationStatus(
            'success',
            `Lat: ${lat}, Lng: ${lng} (precisión: ±${accuracy}m)`
        );
    }

    function requestGeolocation() {
        if (!navigator.geolocation) {
            updateGeolocationStatus('error', 'Geolocalización no soportada por este navegador.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            handleGeolocationSuccess,
            handleGeolocationError,
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 60000
            }
        );
    }

    // Solicitar geolocalización al cargar la página
    requestGeolocation();

    // Botón para reintentar geolocalización
    if (geolocationStatus) {
        geolocationStatus.style.cursor = 'pointer';
        geolocationStatus.title = 'Clic para reintentar';
        geolocationStatus.addEventListener('click', requestGeolocation);
    }
});
</script>
@endpush
