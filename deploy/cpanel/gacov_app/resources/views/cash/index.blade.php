@extends('layouts.app')
@section('title', 'Efectivo a Conductores')

@push('styles')
<style>
.cash-shell {
    color: #0f172a;
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
}

.cash-shell .page-title {
    color: #0f172a;
    letter-spacing: -0.04em;
    font-weight: 900;
}

.cash-shell .page-subtitle {
    color: #475569;
    font-size: 13px;
    font-weight: 500;
}

.cash-shell .cash-toolbar {
    padding: 18px 20px;
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.96));
    border: 1px solid rgba(148,163,184,.28);
    box-shadow: 0 12px 30px rgba(15,23,42,.06);
}

.cash-shell .cash-route-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(59,130,246,.12), rgba(124,58,237,.10));
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid rgba(59,130,246,.16);
}

.cash-shell .cash-route-chip strong {
    color: #0f172a;
    font-weight: 800;
}

.cash-shell .panel {
    border: 1px solid rgba(148,163,184,.26);
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 12px 30px rgba(15,23,42,.05);
}

.cash-shell .panel-header {
    background: #eef2f7;
    border-bottom: 1px solid #e2e8f0;
}

.cash-shell .panel-title {
    color: #0f172a;
    font-weight: 800;
}

.cash-shell .panel-body {
    color: #0f172a;
}

.cash-shell .cash-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 14px;
    align-items: end;
}

.cash-shell .cash-filters .form-label {
    color: #1e293b;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: .01em;
}

.cash-shell .cash-filters .form-input {
    border-color: #cbd5e1;
    background: #fff;
    color: #0f172a;
    box-shadow: 0 1px 0 rgba(15,23,42,.02) inset;
}

.cash-shell .cash-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.cash-shell .cash-metric {
    position: relative;
    overflow: hidden;
    padding: 18px 18px 16px;
    border-radius: 20px;
    border: 1px solid rgba(148,163,184,.22);
    background: linear-gradient(180deg, #fff, #f8fafc);
    box-shadow: 0 12px 30px rgba(15,23,42,.05);
}

.cash-shell .cash-metric::before {
    content: '';
    position: absolute;
    inset: 0 auto auto 0;
    width: 100%;
    height: 4px;
    border-radius: 20px 20px 0 0;
    background: var(--accent, linear-gradient(90deg, #0ea5e9, #8b5cf6));
}

.cash-shell .cash-metric__label {
    color: #475569;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 800;
}

.cash-shell .cash-metric__value {
    margin-top: 10px;
    font-size: 28px;
    line-height: 1.05;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: #0f172a;
}

.cash-shell .cash-table-wrap {
    overflow-x: auto;
    border-radius: 20px;
}

.cash-shell .cash-table {
    min-width: 860px;
}

.cash-shell .cash-table thead th {
    color: #334155;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .08em;
    background: #eef2f7;
    border-bottom: 1px solid #cbd5e1;
}

.cash-shell .cash-table tbody td {
    color: #0f172a;
    font-size: 13px;
    font-weight: 500;
}

.cash-shell .cash-table tbody td.amount-bills {
    color: #0f172a;
    font-weight: 800;
}

.cash-shell .cash-table tbody td.amount-coins {
    color: #ea580c;
    font-weight: 800;
}

.cash-shell .cash-table tbody td.amount-total {
    color: #1d4ed8;
    font-weight: 900;
}

.cash-shell .cash-pill {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(14,165,233,.12);
    color: #0369a1;
    font-size: 12px;
    font-weight: 700;
}

.cash-shell .cash-empty {
    text-align: center;
    padding: 72px 20px;
    color: #475569;
}

.cash-shell .cash-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    border: 1px solid rgba(99,102,241,.18);
    background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(238,242,255,.92));
    color: #4f46e5;
    box-shadow: 0 8px 20px rgba(79,70,229,.10);
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}

.cash-shell .cash-view-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(79,70,229,.16);
    background: linear-gradient(180deg, rgba(255,255,255,1), rgba(224,231,255,.95));
}

@media (max-width: 1024px) {
    .cash-shell .cash-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .cash-shell .cash-metrics {
        grid-template-columns: 1fr;
    }

    .cash-shell .cash-toolbar {
        padding: 16px;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.querySelector('.cash-shell form.cash-filters');
    if (!form) {
        return;
    }

    const autoSubmitFields = form.querySelectorAll('select[name="route_id"], select[name="driver_id"], input[name="date_from"], input[name="date_to"]');
    let timer = null;

    const submitSoon = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }, 120);
    };

    autoSubmitFields.forEach((field) => {
        field.addEventListener('change', submitSoon);
    });
})();
</script>
@endpush

@section('content')
<div class="cash-shell">
<div class="page-header cash-toolbar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <h1 class="page-title">Efectivo a Conductores</h1>
        <p class="page-subtitle">Registro de billetes y monedas entregados para surtir máquinas</p>
        @if($routeFilter)
        <div class="cash-route-chip">
            <span>Filtro activo</span>
            <strong>{{ $routes->firstWhere('id', $routeFilter)?->name ?? 'Ruta asignada' }}</strong>
        </div>
        @endif
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
        <form method="GET" action="{{ route('cash.index') }}" class="cash-filters">
            <div class="form-group" style="margin-bottom:0">
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
            <div class="form-group" style="margin-bottom:0">
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
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Desde</label>
                <input type="date" name="date_from" class="form-input" value="{{ $dateFrom }}">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Hasta</label>
                <input type="date" name="date_to" class="form-input" value="{{ $dateTo }}">
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
            <a href="{{ route('cash.index') }}" style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px;white-space:nowrap">Limpiar</a>
        </form>
    </div>
</div>

{{-- Totales del período --}}
@if($deliveries->isNotEmpty())
<div class="cash-metrics" style="margin-bottom:var(--space-6)">
    @php
        $periodoTotal = $deliveries->sum('total_amount');
        $periodoBills = $deliveries->sum('total_bills');
        $periodoCoins = $deliveries->sum('total_coins');
        $periodoCount = $deliveries->total();
    @endphp
    <div class="cash-metric" style="--accent:linear-gradient(90deg,#f43f5e,#8b5cf6)">
        <p class="cash-metric__label">Total entregado</p>
        <p class="cash-metric__value" style="color:#ef4444">${{ number_format($periodoTotal, 0, ',', '.') }}</p>
    </div>
    <div class="cash-metric" style="--accent:linear-gradient(90deg,#10b981,#34d399)">
        <p class="cash-metric__label">En billetes</p>
        <p class="cash-metric__value" style="color:#16a34a">${{ number_format($periodoBills, 0, ',', '.') }}</p>
    </div>
    <div class="cash-metric" style="--accent:linear-gradient(90deg,#f59e0b,#f97316)">
        <p class="cash-metric__label">En monedas</p>
        <p class="cash-metric__value" style="color:#ea580c">${{ number_format($periodoCoins, 0, ',', '.') }}</p>
    </div>
    <div class="cash-metric" style="--accent:linear-gradient(90deg,#0ea5e9,#22c55e)">
        <p class="cash-metric__label">Registros</p>
        <p class="cash-metric__value">{{ $periodoCount }}</p>
    </div>
</div>
@endif

{{-- Tabla --}}
<div class="panel">
    @if($deliveries->isNotEmpty())
    <div class="cash-table-wrap">
    <table class="data-table cash-table">
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
                    <span class="cash-pill">{{ $delivery->route->name }}</span>
                    @else
                    <span style="color:var(--gacov-text-muted)">Sin ruta</span>
                    @endif
                </td>
                <td class="amount-bills" style="text-align:right">
                    ${{ number_format($delivery->total_bills, 0, ',', '.') }}
                </td>
                <td class="amount-coins" style="text-align:right">
                    ${{ number_format($delivery->total_coins, 0, ',', '.') }}
                </td>
                <td class="amount-total" style="text-align:right">
                    ${{ number_format($delivery->total_amount, 0, ',', '.') }}
                </td>
                <td style="font-size:12px;color:var(--gacov-text-muted)">
                    {{ $delivery->deliveredBy?->name ?? '—' }}
                </td>
                <td>
                    <a href="{{ route('cash.show', $delivery) }}"
                       class="cash-view-btn"
                       title="Ver detalle"
                       aria-label="Ver detalle">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
                            <path d="M10 3c5.5 0 8.5 5.5 8.5 7s-3 7-8.5 7S1.5 11.5 1.5 10 4.5 3 10 3zm0 2C5.9 5 3.2 8.7 3 10c.2 1.3 2.9 5 7 5s6.8-3.7 7-5c-.2-1.3-2.9-5-7-5zm0 1.5A3.5 3.5 0 1 1 6.5 10 3.5 3.5 0 0 1 10 6.5z"/>
                        </svg>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    @if($deliveries->hasPages())
    <div style="padding:var(--space-4) var(--space-6);border-top:1px solid var(--gacov-border)">
        {{ $deliveries->links() }}
    </div>
    @endif

    @else
    <div class="cash-empty">
        <svg viewBox="0 0 20 20" fill="currentColor" width="40" height="40" style="margin:0 auto var(--space-4);opacity:.3"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/></svg>
        <p style="font-size:15px;font-weight:500;margin-bottom:var(--space-2)">Sin entregas</p>
        <p style="font-size:13px">No se encontraron entregas en el período seleccionado.</p>
        @can('cash.manage')
        <a href="{{ route('cash.create') }}" class="btn btn-primary" style="margin-top:var(--space-4);width:auto">Registrar primera entrega</a>
        @endcan
    </div>
    @endif
</div>
</div>
@endsection
