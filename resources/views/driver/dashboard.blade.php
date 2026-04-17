@extends('layouts.app')
@section('title', 'Mi Ruta')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #driver-map {
        height: 300px;
        border-radius: var(--radius-md);
        margin-bottom: var(--space-5);
    }
    .machine-popup-content {
        min-width: 180px;
    }
    .machine-popup-content h4 {
        margin: 0 0 6px 0;
        font-size: 14px;
        color: #1a1a2e;
    }
    .machine-popup-content p {
        margin: 0 0 4px 0;
        font-size: 12px;
        color: #555;
    }
    .machine-popup-content .stock-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .stock-badge.good { background: #d1fae5; color: #065f46; }
    .stock-badge.low { background: #fef3c7; color: #92400e; }
    .stock-badge.critical { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
@php($routeQuery = $route?->id ? ['route_id' => $route->id] : [])
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <h1 class="page-title">Hola, {{ auth()->user()->name }}</h1>
        <p class="page-subtitle">
            @if($route)
                Ruta {{ $route->code }} — {{ $route->name }}
                @if($route->vehicle_plate) · Vehículo: {{ $route->vehicle_plate }} @endif
            @else
                Sin ruta asignada
            @endif
        </p>
    </div>
    <div class="driver-header-actions">
        @moduleEnabled('drivers')
        <a href="{{ route('driver.stocking.create', $routeQuery) }}" class="btn btn-primary" style="width:auto">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg>
            Surtir máquina
        </a>
        @endmoduleEnabled
        @moduleEnabled('sales')
        <a href="{{ route('driver.sales.create', $routeQuery) }}" class="btn" style="background:var(--gacov-bg-elevated);color:var(--gacov-text-primary);width:auto">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
            Registrar venta
        </a>
        @endmoduleEnabled
    </div>
</div>

@if($availableRoutes->isNotEmpty())
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body">
        <form method="GET" action="{{ route('driver.dashboard') }}" class="driver-route-switcher">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="driver-dashboard-route-id">Ruta activa</label>
                <select id="driver-dashboard-route-id" name="route_id" class="form-input" onchange="this.form.submit()">
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

<div class="driver-quick-grid">
    @moduleEnabled('drivers')
    <a href="{{ route('driver.stocking.create', $routeQuery) }}" class="driver-quick-card">
        <div class="driver-quick-title">Surtido</div>
        <div class="driver-quick-copy">Cargar planilla o surtir una máquina de la ruta.</div>
    </a>
    @endmoduleEnabled
    @moduleEnabled('sales')
    <a href="{{ route('driver.sales.create', $routeQuery) }}" class="driver-quick-card">
        <div class="driver-quick-title">Ventas</div>
        <div class="driver-quick-copy">Registrar ventas desde la máquina seleccionada.</div>
    </a>
    @endmoduleEnabled
    @can('vehicle.inventory.view')
    @moduleEnabled('inventory')
    <a href="{{ route('driver.inventory', $routeQuery) }}" class="driver-quick-card">
        <div class="driver-quick-title">Vehículo</div>
        <div class="driver-quick-copy">Consultar inventario disponible en el vehículo.</div>
    </a>
    @endmoduleEnabled
    @endcan
</div>

{{-- KPIs del día --}}
<div class="kpi-grid" style="margin-bottom:var(--space-8)">
    <div class="kpi-card" style="--kpi-accent:#D71920;--kpi-bg:rgba(215,25,32,.08)">
        <div class="kpi-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg>
        </div>
        <div class="kpi-value">{{ $todayStockings }}</div>
        <div class="kpi-label">Surtidos hoy</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#6B7280;--kpi-bg:rgba(107,114,128,.10)">
        <div class="kpi-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
        </div>
        <div class="kpi-value">{{ $todaySales }}</div>
        <div class="kpi-label">Ventas registradas hoy</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#111111;--kpi-bg:rgba(17,17,17,.08)">
        <div class="kpi-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
        </div>
        <div class="kpi-value">${{ number_format((float)$todaySalesAmount, 0, ',', '.') }}</div>
        <div class="kpi-label">Total vendido hoy (COP)</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#E5E7EB;--kpi-bg:rgba(229,231,235,.65)">
        <div class="kpi-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
        </div>
        <div class="kpi-value">{{ $machines->count() }}</div>
        <div class="kpi-label">Máquinas en mi ruta</div>
    </div>
</div>

{{-- Mapa de máquinas --}}
@if($machines->isNotEmpty() && $machines->contains(fn($m) => $m->latitude && $m->longitude))
<div class="panel" style="margin-bottom:var(--space-8)">
    <div class="panel-header">
        <span class="panel-title">Ubicación de máquinas</span>
        <span class="badge badge-info">{{ $machines->filter(fn($m) => $m->latitude && $m->longitude)->count() }} máquinas con GPS</span>
    </div>
    <div class="panel-body">
        <div id="driver-map"></div>
    </div>
</div>
@endif

{{-- Lista de máquinas --}}
<div class="panel">
    <div class="panel-header">
        <span class="panel-title">Máquinas de mi ruta</span>
        @can('vehicle.inventory.view')
        @moduleEnabled('inventory')
        <a href="{{ route('driver.inventory', $routeQuery) }}" style="font-size:13px;color:var(--gacov-primary);text-decoration:none">
            Ver inventario vehículo →
        </a>
        @endmoduleEnabled
        @endcan
    </div>

    @if($machines->isNotEmpty())
    <div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre / Ubicación</th>
                <th>Stock total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($machines as $machine)
            <tr>
                <td><strong style="color:var(--gacov-primary)">{{ $machine->code }}</strong></td>
                <td>
                    <div>{{ $machine->name }}</div>
                    @if($machine->location)
                    <div style="font-size:12px;color:var(--gacov-text-muted)">{{ $machine->location }}</div>
                    @endif
                </td>
                <td>
                    <span style="font-weight:600;color:{{ $machine->total_stock < 10 ? 'var(--gacov-error)' : ($machine->total_stock < 30 ? 'var(--gacov-warning)' : 'var(--gacov-success)') }}">
                        {{ $machine->total_stock }} uds.
                    </span>
                </td>
                <td>
                    @if($machine->total_stock < 10)
                        <span class="badge badge-error">Necesita surtido</span>
                    @elseif($machine->total_stock < 30)
                        <span class="badge badge-warning">Stock bajo</span>
                    @else
                        <span class="badge badge-success">Bien surtida</span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;gap:var(--space-2)">
                        @moduleEnabled('drivers')
                        @php($stockingParams = array_merge($routeQuery, ['machine_id' => $machine->id]))
                        <a
                           href="{{ route('driver.stocking.create', $stockingParams) }}"
                           style="padding:4px 10px;background:rgba(215,25,32,.10);color:var(--gacov-primary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                            Surtir
                        </a>
                        @endmoduleEnabled
                        @moduleEnabled('sales')
                        @php($salesParams = array_merge($routeQuery, ['machine_id' => $machine->id]))
                        <a href="{{ route('driver.sales.create', $salesParams) }}"
                           style="padding:4px 10px;background:rgba(229,231,235,.16);color:var(--gacov-success);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                            Venta
                        </a>
                        @endmoduleEnabled
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-10) 0;color:var(--gacov-text-muted)">
        <p>No hay máquinas asignadas a tu ruta.</p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos de máquinas con coordenadas
    const machinesWithCoords = [
        @foreach($machines->filter(fn($m) => $m->latitude && $m->longitude) as $machine)
        {
            id: {{ $machine->id }},
            code: "{{ $machine->code }}",
            name: "{{ $machine->name }}",
            location: "{{ $machine->location ?? 'Sin ubicación' }}",
            latitude: {{ $machine->latitude }},
            longitude: {{ $machine->longitude }},
            stock: {{ $machine->total_stock ?? 0 }},
            stockStatus: "{{ $machine->total_stock < 10 ? 'critical' : ($machine->total_stock < 30 ? 'low' : 'good') }}"
        },
        @endforeach
    ];

    if (machinesWithCoords.length === 0) {
        return;
    }

    // Inicializar mapa centrado en la primera máquina
    const firstMachine = machinesWithCoords[0];
    const map = L.map('driver-map').setView([firstMachine.latitude, firstMachine.longitude], 13);

    // Capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    // Icono personalizado para máquinas
    function createMachineIcon(stockStatus) {
        const color = stockStatus === 'critical' ? '#ef4444' : (stockStatus === 'low' ? '#f59e0b' : '#10b981');
        return L.divIcon({
            className: 'machine-marker',
            html: `<div style="
                background:${color};
                width:32px;
                height:32px;
                border-radius:50%;
                border:3px solid white;
                box-shadow:0 2px 8px rgba(0,0,0,0.3);
                display:flex;
                align-items:center;
                justify-content:center;
            ">
                <svg viewBox="0 0 20 20" fill="white" width="16" height="16">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 7.5a1.5 1.5 0 113 0V11a1 1 0 01-2 0V7.5zM8 13a1 1 0 100-2 1 1 0 000 2z"/>
                </svg>
            </div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });
    }

    // Agregar marcadores
    let bounds = L.latLngBounds([]);
    machinesWithCoords.forEach(function(machine) {
        const stockLabel = machine.stockStatus === 'critical' ? 'Necesita surtido' :
                          (machine.stockStatus === 'low' ? 'Stock bajo' : 'Bien surtida');

        const popupContent = `
            <div class="machine-popup-content">
                <h4>${machine.code} — ${machine.name}</h4>
                <p><strong>Ubicación:</strong> ${machine.location}</p>
                <p><strong>Stock:</strong> ${machine.stock} uds.</p>
                <span class="stock-badge ${machine.stockStatus}">${stockLabel}</span>
            </div>
        `;

        const marker = L.marker([machine.latitude, machine.longitude], {
            icon: createMachineIcon(machine.stockStatus)
        }).addTo(map);

        marker.bindPopup(popupContent);
        bounds.extend([machine.latitude, machine.longitude]);
    });

    // Ajustar vista para mostrar todos los marcadores
    if (machinesWithCoords.length > 1) {
        map.fitBounds(bounds, { padding: [30, 30] });
    }
});
</script>
@endpush
