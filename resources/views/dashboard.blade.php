@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Resumen general del sistema de inventarios — {{ now()->format('d/m/Y') }}</p>
</div>

{{-- KPI Cards --}}
<div class="kpi-grid">

    <div class="kpi-card" style="--kpi-accent:#00D4FF;--kpi-bg:rgba(0,212,255,.1)">
        <div class="kpi-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
        </div>
        <div class="kpi-value">{{ $stats['total_products'] ?? '—' }}</div>
        <div class="kpi-label">Productos activos</div>
        <div class="kpi-trend neutral">
            <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
            En bodega principal
        </div>
    </div>

    <div class="kpi-card" style="--kpi-accent:#7C3AED;--kpi-bg:rgba(124,58,237,.1)">
        <div class="kpi-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
        </div>
        <div class="kpi-value">{{ $stats['total_machines'] ?? '—' }}</div>
        <div class="kpi-label">Máquinas activas</div>
        <div class="kpi-trend neutral">
            <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
            2 rutas
        </div>
    </div>

    <div class="kpi-card" style="--kpi-accent:#10B981;--kpi-bg:rgba(16,185,129,.1)">
        <div class="kpi-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8z"/><path d="M12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/></svg>
        </div>
        <div class="kpi-value">{{ $stats['movements_today'] ?? 0 }}</div>
        <div class="kpi-label">Movimientos hoy</div>
        <div class="kpi-trend neutral">Traslados y surtidos</div>
    </div>

    <div class="kpi-card" style="--kpi-accent:#F59E0B;--kpi-bg:rgba(245,158,11,.1)">
        <div class="kpi-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        </div>
        <div class="kpi-value">{{ $stats['low_stock_machines'] ?? 0 }}</div>
        <div class="kpi-label">Máquinas con stock bajo</div>
        <div class="kpi-trend {{ ($stats['low_stock_machines'] ?? 0) > 0 ? 'down' : 'up' }}">
            @if(($stats['low_stock_machines'] ?? 0) > 0)
                Requieren surtido
            @else
                Todo en orden
            @endif
        </div>
    </div>

</div>

{{-- Panels row --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">

    {{-- Últimos movimientos --}}
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">Últimos movimientos</span>
            <span class="badge badge-neutral">Hoy</span>
        </div>
        @if(isset($recentMovements) && $recentMovements->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th>Cant.</th>
                    <th>Hora</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentMovements as $m)
                <tr>
                    <td>{{ $m->product->name ?? '—' }}</td>
                    <td><span class="badge badge-info">{{ str_replace('_',' ', $m->movement_type) }}</span></td>
                    <td>{{ number_format((float) $m->quantity, 0, ',', '.') }}</td>
                    <td>{{ \Carbon\Carbon::parse($m->created_at)->format('H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="panel-body" style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-10) 0">
            <svg viewBox="0 0 20 20" fill="currentColor" width="32" height="32" style="margin:0 auto var(--space-3);opacity:.3"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
            <p>Sin movimientos hoy</p>
        </div>
        @endif
    </div>

    {{-- Rutas activas --}}
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">Rutas y conductores</span>
            <span class="badge badge-success">Activas</span>
        </div>
        @if(isset($routes) && $routes->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ruta</th>
                    <th>Conductor</th>
                    <th>Vehículo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($routes as $r)
                <tr>
                    <td><strong>{{ $r->code }}</strong> — {{ $r->name }}</td>
                    <td>{{ $r->driver->name ?? '—' }}</td>
                    <td>{{ $r->vehicle_plate ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $r->is_active ? 'badge-success' : 'badge-neutral' }}">
                            {{ $r->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="panel-body" style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-10) 0">
            <p>Sin rutas configuradas</p>
        </div>
        @endif
    </div>

</div>

@endsection
