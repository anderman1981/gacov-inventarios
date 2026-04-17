@extends('layouts.app')
@section('title', 'Efectivo a Conductores')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <h1 class="page-title">Efectivo a Conductores</h1>
        <p class="page-subtitle">Registro de billetes y monedas entregados para surtir máquinas</p>
    </div>
    @can('cash.manage')
    <a href="{{ route('cash.create') }}" class="btn btn-primary" style="width:auto">
        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Nueva entrega
    </a>
    @endcan
</div>

{{-- Filtros --}}
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6)">
        <form method="GET" action="{{ route('cash.index') }}" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="min-width:160px;margin-bottom:0">
                <label class="form-label">Ruta</label>
                <select name="route_id" class="form-input">
                    <option value="">Todas las rutas</option>
                    @foreach($routes as $route)
                    <option value="{{ $route->id }}" {{ $routeFilter == $route->id ? 'selected' : '' }}>
                        {{ $route->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label class="form-label">Conductor</label>
                <select name="driver_id" class="form-input">
                    <option value="">Todos los conductores</option>
                    @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ $driverFilter == $driver->id ? 'selected' : '' }}>
                        {{ $driver->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="min-width:140px;margin-bottom:0">
                <label class="form-label">Desde</label>
                <input type="date" name="date_from" class="form-input" value="{{ $dateFrom }}">
            </div>
            <div class="form-group" style="min-width:140px;margin-bottom:0">
                <label class="form-label">Hasta</label>
                <input type="date" name="date_to" class="form-input" value="{{ $dateTo }}">
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
            <a href="{{ route('cash.index') }}" style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px">Limpiar</a>
        </form>
    </div>
</div>

{{-- Totales del período --}}
@if($deliveries->isNotEmpty())
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--space-4);margin-bottom:var(--space-6)">
    @php
        $periodoTotal = $deliveries->sum('total_amount');
        $periodoBills = $deliveries->sum('total_bills');
        $periodoCoins = $deliveries->sum('total_coins');
        $periodoCount = $deliveries->total();
    @endphp
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">Total entregado</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-primary)">${{ number_format($periodoTotal, 0, ',', '.') }}</p>
    </div>
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">En billetes</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-success)">${{ number_format($periodoBills, 0, ',', '.') }}</p>
    </div>
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">En monedas</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-warning)">${{ number_format($periodoCoins, 0, ',', '.') }}</p>
    </div>
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">Registros</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-text-primary)">{{ $periodoCount }}</p>
    </div>
</div>
@endif

{{-- Tabla --}}
<div class="panel">
    @if($deliveries->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Conductor</th>
                <th>Ruta</th>
                <th style="text-align:right">Billetes</th>
                <th style="text-align:right">Monedas</th>
                <th style="text-align:right">Total</th>
                <th>Entregó</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveries as $delivery)
            <tr>
                <td style="font-size:13px;color:var(--gacov-text-secondary);white-space:nowrap">
                    {{ $delivery->delivery_date->format('d/m/Y') }}
                </td>
                <td>
                    <span style="font-weight:600">{{ $delivery->driver?->name ?? '—' }}</span>
                </td>
                <td>
                    @if($delivery->route)
                    <span class="badge badge-info">{{ $delivery->route->name }}</span>
                    @else
                    <span style="color:var(--gacov-text-muted)">Sin ruta</span>
                    @endif
                </td>
                <td style="text-align:right;color:var(--gacov-success);font-weight:600">
                    ${{ number_format($delivery->total_bills, 0, ',', '.') }}
                </td>
                <td style="text-align:right;color:var(--gacov-warning);font-weight:600">
                    ${{ number_format($delivery->total_coins, 0, ',', '.') }}
                </td>
                <td style="text-align:right;font-weight:700;color:var(--gacov-primary)">
                    ${{ number_format($delivery->total_amount, 0, ',', '.') }}
                </td>
                <td style="font-size:12px;color:var(--gacov-text-muted)">
                    {{ $delivery->deliveredBy?->name ?? '—' }}
                </td>
                <td>
                    <a href="{{ route('cash.show', $delivery) }}"
                       style="padding:4px 10px;background:rgba(0,212,255,.1);color:var(--gacov-primary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                        Ver
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($deliveries->hasPages())
    <div style="padding:var(--space-4) var(--space-6);border-top:1px solid var(--gacov-border)">
        {{ $deliveries->links() }}
    </div>
    @endif

    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-12) 0;color:var(--gacov-text-muted)">
        <svg viewBox="0 0 20 20" fill="currentColor" width="40" height="40" style="margin:0 auto var(--space-4);opacity:.3"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/></svg>
        <p style="font-size:15px;font-weight:500;margin-bottom:var(--space-2)">Sin entregas</p>
        <p style="font-size:13px">No se encontraron entregas en el período seleccionado.</p>
        @can('cash.manage')
        <a href="{{ route('cash.create') }}" class="btn btn-primary" style="margin-top:var(--space-4);width:auto">Registrar primera entrega</a>
        @endcan
    </div>
    @endif
</div>
@endsection
