@extends('layouts.app')
@section('title', 'Inventario vehículo')

@section('content')
@php
    $routeQuery = $route?->id ? ['route_id' => $route->id] : [];
@endphp
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between">
    <div>
        <h1 class="page-title">Inventario del vehículo</h1>
        <p class="page-subtitle">
            @if($route)
                Ruta {{ $route->code }} — {{ $route->name }}
            @endif

            @if($vehicleWarehouse)
                · {{ $vehicleWarehouse->name }}
            @endif
        </p>
    </div>
    <a href="{{ route('driver.dashboard', $routeQuery) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
        ← Volver
    </a>
</div>

@if($availableRoutes->isNotEmpty())
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body">
        <form method="GET" action="{{ route('driver.inventory') }}" class="driver-route-switcher">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="driver-inventory-route-id">Seleccionar ruta</label>
                <select id="driver-inventory-route-id" name="route_id" class="form-input" onchange="this.form.submit()">
                    <option value="">Seleccionar ruta...</option>
                    @foreach($availableRoutes as $availableRoute)
                    <option value="{{ $availableRoute->id }}" {{ $route?->id === $availableRoute->id ? 'selected' : '' }}>
                        {{ $availableRoute->code }} — {{ $availableRoute->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="btn btn-primary" style="width:auto">Cargar ruta</button></noscript>
        </form>
    </div>
</div>
@endif

@if(!$vehicleWarehouse)
<div class="alert alert-error">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    No hay bodega de vehículo configurada para tu ruta.
</div>
@else
<div class="panel">
    <div class="panel-header">
        <span class="panel-title">Stock disponible en el vehículo</span>
        <span class="badge badge-neutral">{{ $stocks->count() }} productos</span>
    </div>
    @if($stocks->isNotEmpty())
    <div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th style="text-align:center">Cantidad</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stocks as $stock)
            @php
                $category = $stock->product->category;
                $stockQuantity = (float) $stock->quantity;
                $categoryBadgeClass = match ($category) {
                    'snacks' => 'badge-info',
                    'bebidas_frias' => 'badge-neutral',
                    default => 'badge-warning',
                };
                $stockLevelColor = match (true) {
                    $stockQuantity < 5 => 'var(--gacov-error)',
                    $stockQuantity < 15 => 'var(--gacov-warning)',
                    default => 'var(--gacov-success)',
                };
                [$stockStatusBadgeClass, $stockStatusLabel] = match (true) {
                    $stockQuantity < 5 => ['badge-error', 'Agotándose'],
                    $stockQuantity < 15 => ['badge-warning', 'Stock bajo'],
                    default => ['badge-success', 'OK'],
                };
            @endphp
            <tr>
                <td>
                    <strong>{{ $stock->product->name }}</strong>
                    <div style="font-size:11px;color:var(--gacov-text-muted)">{{ $stock->product->sku }}</div>
                </td>
                <td>
                    <span class="badge {{ $categoryBadgeClass }}">
                        {{ str_replace('_', ' ', $category) }}
                    </span>
                </td>
                <td style="color:var(--gacov-text-muted)">{{ $stock->product->unit }}</td>
                <td style="text-align:center">
                    <span style="font-weight:700;font-size:18px;color:{{ $stockLevelColor }}">
                        {{ number_format($stockQuantity, 0, ',', '.') }}
                    </span>
                </td>
                <td>
                    <span class="badge {{ $stockStatusBadgeClass }}">{{ $stockStatusLabel }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-10) 0;color:var(--gacov-text-muted)">
        <p>El vehículo no tiene productos cargados actualmente.</p>
    </div>
    @endif
</div>
@endif
@endsection
