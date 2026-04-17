@extends('layouts.app')

@php
    $role = auth()->user()?->roles->first()?->name ?? 'guest';
    $isAdmin   = in_array($role, ['admin', 'super_admin', 'manager']);
    $isSuperAdmin = $role === 'super_admin';
    $isManager = $role === 'manager';
    $isContador = $role === 'contador';
    $movementTrendValues = array_map(static fn (array $row): int => (int) ($row['value'] ?? 0), $movementTrend ?? []);
    $topSoldProductsValues = array_map(static fn (array $row): int => (int) ($row['units'] ?? 0), $topSoldProducts ?? []);
    $salesByLocationValues = array_map(static fn (array $row): float => (float) ($row['revenue'] ?? 0), $salesByLocation ?? []);
    $salesByMachineValues = array_map(static fn (array $row): float => (float) ($row['revenue'] ?? 0), $salesByMachine ?? []);
    $movementTrendMax = $movementTrendValues !== [] ? max($movementTrendValues) : 1;
    $topSoldProductsMax = $topSoldProductsValues !== [] ? max($topSoldProductsValues) : 1;
    $salesByLocationMax = $salesByLocationValues !== [] ? max($salesByLocationValues) : 1;
    $salesByMachineMax = $salesByMachineValues !== [] ? max($salesByMachineValues) : 1;
    $movementTrendMax = $movementTrendMax > 0 ? $movementTrendMax : 1;
    $topSoldProductsMax = $topSoldProductsMax > 0 ? $topSoldProductsMax : 1;
    $salesByLocationMax = $salesByLocationMax > 0 ? $salesByLocationMax : 1;
    $salesByMachineMax = $salesByMachineMax > 0 ? $salesByMachineMax : 1;
@endphp

@section('title', 'Dashboard')

{{-- ── Acciones de cabecera por rol ─────────────────────────── --}}
@section('header-actions')
    @if($isAdmin)
        @can('reports.worldoffice')
        <a href="{{ route('worldoffice.index') }}" class="btn btn-secondary btn-sm">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
            Exportar WorldOffice
        </a>
        @endcan
        @can('transfers.create')
        @moduleEnabled('transfers')
        <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Nueva orden de traslado
        </a>
        @endmoduleEnabled
        @endcan
        @can('inventory.adjust')
        @moduleEnabled('inventory')
        <a href="{{ route('inventory.adjust') }}" class="btn btn-secondary btn-sm">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
                <path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/>
            </svg>
            Ajuste de inventario
        </a>
        @endmoduleEnabled
        @endcan
    @elseif($isContador)
        @can('reports.worldoffice')
        <a href="{{ route('worldoffice.index') }}" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
            Exportar WorldOffice
        </a>
        @endcan
        @can('movements.view')
        @moduleEnabled('reports')
        <a href="{{ route('inventory.movements') }}" class="btn btn-secondary btn-sm">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"/>
            </svg>
            Ver reportes
        </a>
        @endmoduleEnabled
        @endcan
    @endif
@endsection

@section('content')

{{-- ── Encabezado de página ──────────────────────────────────── --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            @if($isAdmin) Dashboard Administrativo
            @elseif($isContador) Dashboard Financiero
            @else Dashboard
            @endif
        </h1>
        <p class="page-subtitle">
            @if($isAdmin)
                Visión completa del sistema — {{ now()->format('d/m/Y') }}
            @elseif($isContador)
                Resumen financiero y movimientos — {{ now()->format('d/m/Y') }}
            @else
                Resumen general del sistema — {{ now()->format('d/m/Y') }}
            @endif
        </p>
    </div>

    {{-- Acciones inline para pantallas medianas --}}
    <div class="page-header-actions" style="display:flex;gap:var(--space-3);flex-wrap:wrap;">
        @if($isAdmin)
            @can('reports.worldoffice')
            <a href="{{ route('worldoffice.index') }}" class="btn btn-secondary btn-sm">
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                Exportar WorldOffice
            </a>
            @endcan
            @can('transfers.create')
            @moduleEnabled('transfers')
            <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm">
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                Nueva orden de traslado
            </a>
            @endmoduleEnabled
            @endcan
            @can('inventory.adjust')
            @moduleEnabled('inventory')
            <a href="{{ route('inventory.adjust') }}" class="btn btn-secondary btn-sm">
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
                Ajuste de inventario
            </a>
            @endmoduleEnabled
            @endcan
        @elseif($isContador)
            @can('reports.worldoffice')
            <a href="{{ route('worldoffice.index') }}" class="btn btn-primary btn-sm">
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                Exportar WorldOffice
            </a>
            @endcan
            @can('movements.view')
            @moduleEnabled('reports')
            <a href="{{ route('inventory.movements') }}" class="btn btn-secondary btn-sm">
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"/></svg>
                Ver reportes
            </a>
            @endmoduleEnabled
            @endcan
        @endif
    </div>
</div>

@if(in_array($role, ['admin', 'super_admin'], true) && isset($inventoryNotifications) && $inventoryNotifications->isNotEmpty())
<div class="panel" style="margin-bottom:var(--space-6);border:1px solid rgba(215,25,32,.22)">
    <div class="panel-header">
        <span class="panel-title">Ajustes pendientes de revisión</span>
        <span class="badge badge-warning">{{ $inventoryNotifications->count() }} nuevas</span>
    </div>
    <div class="panel-body" style="display:grid;gap:var(--space-4)">
        @foreach($inventoryNotifications as $notification)
            @php($payload = $notification->data)
            <div style="padding:var(--space-4);border:1px solid var(--gacov-border);border-radius:var(--radius-lg);background:var(--gacov-bg-elevated)">
                <div style="display:flex;justify-content:space-between;gap:var(--space-3);align-items:flex-start">
                    <div>
                        <strong>{{ $payload['title'] ?? 'Ajuste de inventario pendiente' }}</strong>
                        <p style="margin-top:6px;color:var(--gacov-text-secondary)">{{ $payload['message'] ?? '' }}</p>
                    </div>
                    <span class="badge badge-neutral">{{ $notification->created_at?->diffForHumans() }}</span>
                </div>
                @if(!empty($payload['reason']))
                <p style="margin-top:var(--space-3);font-size:13px;color:var(--gacov-text-secondary)">
                    <strong>Observación:</strong> {{ $payload['reason'] }}
                </p>
                @endif
                <div style="margin-top:var(--space-3)">
                    <a href="{{ $payload['action_url'] ?? route('inventory.movements') }}" class="btn btn-secondary btn-sm" style="width:auto">
                        Revisar movimientos
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- ADMIN / MANAGER — Dashboard completo                        --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($isAdmin)
    <div class="dashboard-kpi-row dashboard-kpi-row--six">
        <div class="kpi-card" style="--kpi-accent:#D71920;--kpi-bg:rgba(215,25,32,.08)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg></div>
            <div class="kpi-value">{{ $stats['total_products'] ?? '—' }}</div>
            <div class="kpi-label">Productos activos</div>
            <div class="kpi-trend neutral">En bodega principal</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#111111;--kpi-bg:rgba(17,17,17,.08)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg></div>
            <div class="kpi-value">{{ $stats['total_machines'] ?? '—' }}</div>
            <div class="kpi-label">Máquinas activas</div>
            <div class="kpi-trend neutral">{{ $stats['active_routes'] ?? 0 }} rutas configuradas</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#6B7280;--kpi-bg:rgba(107,114,128,.10)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/></svg></div>
            <div class="kpi-value">{{ $stats['movements_today'] ?? 0 }}</div>
            <div class="kpi-label">Movimientos hoy</div>
            <div class="kpi-trend neutral">Traslados y surtidos</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#E5E7EB;--kpi-bg:rgba(229,231,235,.65)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg></div>
            <div class="kpi-value">{{ $stats['low_stock_machines'] ?? 0 }}</div>
            <div class="kpi-label">Máquinas stock bajo</div>
            <div class="kpi-trend {{ ($stats['low_stock_machines'] ?? 0) > 0 ? 'down' : 'up' }}">
                {{ ($stats['low_stock_machines'] ?? 0) > 0 ? 'Requieren surtido' : 'Todo en orden' }}
            </div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#D71920;--kpi-bg:rgba(215,25,32,.08)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"/></svg></div>
            <div class="kpi-value">{{ $stats['pending_transfers'] ?? 0 }}</div>
            <div class="kpi-label">Traslados pendientes</div>
            <div class="kpi-trend {{ ($stats['pending_transfers'] ?? 0) > 0 ? 'down' : 'up' }}">
                {{ ($stats['pending_transfers'] ?? 0) > 0 ? 'Requieren atención' : 'Sin pendientes' }}
            </div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#D71920;--kpi-bg:rgba(215,25,32,.08)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg></div>
            <div class="kpi-value">{{ $stats['active_routes'] ?? 0 }}</div>
            <div class="kpi-label">Rutas activas</div>
            <div class="kpi-trend neutral">Conductores en campo</div>
        </div>
    </div>

    <div class="dashboard-chart-grid">
        <section class="dashboard-chart-card dashboard-chart-card--wide">
            <div class="dashboard-chart-card__header">
                <div>
                    <div class="dashboard-chart-card__title">Últimos movimientos</div>
                    <div class="dashboard-chart-card__subtitle">Comportamiento diario de los últimos 7 días.</div>
                </div>
                <span class="badge badge-neutral">7 días</span>
            </div>
            <div class="dashboard-chart-card__body">
                <div class="chart-bars">
                    @foreach($movementTrend ?? [] as $point)
                        @php($movementHeight = max(8, (int) round((($point['value'] ?? 0) / $movementTrendMax) * 100)))
                        <div class="chart-bars__item">
                            <div class="chart-bars__value">{{ $point['value'] ?? 0 }}</div>
                            <div class="chart-bars__track">
                                <span style="height: {{ $movementHeight }}%; --chart-accent:#D71920"></span>
                            </div>
                            <div class="chart-bars__label">{{ $point['label'] ?? '—' }}</div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:var(--space-4);display:grid;gap:10px">
                    @forelse($recentMovements->take(4) as $movement)
                        <div style="display:flex;justify-content:space-between;gap:var(--space-3);align-items:center;padding:10px 12px;border:1px solid rgba(148,163,184,.16);border-radius:16px;background:#fff">
                            <div style="min-width:0">
                                <strong style="display:block;font-size:13px;color:var(--amr-app-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    {{ $movement->product->name ?? '—' }}
                                </strong>
                                <span style="font-size:11px;color:var(--amr-app-text-muted)">{{ \Carbon\Carbon::parse($movement->created_at)->format('d/m H:i') }}</span>
                            </div>
                            <span class="badge badge-info" style="white-space:nowrap">{{ str_replace('_', ' ', $movement->movement_type) }}</span>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-4) 0">
                            <p style="margin:0">Sin movimientos recientes</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="dashboard-chart-card dashboard-chart-card--half">
            <div class="dashboard-chart-card__header">
                <div>
                    <div class="dashboard-chart-card__title">Productos más vendidos</div>
                    <div class="dashboard-chart-card__subtitle">Unidades vendidas en los últimos 30 días.</div>
                </div>
                <span class="badge badge-neutral">Top 5</span>
            </div>
            <div class="dashboard-chart-card__body">
                <div class="chart-rank">
                    @forelse($topSoldProducts ?? [] as $product)
                        @php($productWidth = max(8, (int) round((($product['units'] ?? 0) / $topSoldProductsMax) * 100)))
                        <div class="chart-rank__row">
                            <div class="chart-rank__head">
                                <div class="chart-rank__title">
                                    {{ filled($product['code'] ?? '') ? ($product['code'] . ' — ') : '' }}{{ $product['label'] ?? '—' }}
                                </div>
                                <div class="chart-rank__meta">{{ number_format((float) ($product['units'] ?? 0), 0, ',', '.') }} un</div>
                            </div>
                            <div class="chart-rank__bar"><span style="width: {{ $productWidth }}%; --chart-accent:#111111"></span></div>
                            <div class="chart-rank__foot">${{ number_format((float) ($product['revenue'] ?? 0), 0, ',', '.') }} facturados</div>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-4) 0">
                            <p style="margin:0">Sin ventas registradas</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="dashboard-chart-card dashboard-chart-card--third">
            <div class="dashboard-chart-card__header">
                <div>
                    <div class="dashboard-chart-card__title">Ventas por ubicación</div>
                    <div class="dashboard-chart-card__subtitle">Comparativo de la facturación por punto.</div>
                </div>
                <span class="badge badge-neutral">Ubicación</span>
            </div>
            <div class="dashboard-chart-card__body">
                <div class="chart-rank">
                    @forelse($salesByLocation ?? [] as $locationRow)
                        @php($locationWidth = max(8, (int) round((($locationRow['revenue'] ?? 0) / $salesByLocationMax) * 100)))
                        <div class="chart-rank__row">
                            <div class="chart-rank__head">
                                <div class="chart-rank__title">{{ $locationRow['label'] ?? '—' }}</div>
                                <div class="chart-rank__meta">${{ number_format((float) ($locationRow['revenue'] ?? 0), 0, ',', '.') }}</div>
                            </div>
                            <div class="chart-rank__bar"><span style="width: {{ $locationWidth }}%; --chart-accent:#6B7280"></span></div>
                            <div class="chart-rank__foot">{{ number_format((float) ($locationRow['units'] ?? 0), 0, ',', '.') }} unidades</div>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-4) 0">
                            <p style="margin:0">Sin datos por ubicación</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="dashboard-chart-card dashboard-chart-card--third">
            <div class="dashboard-chart-card__header">
                <div>
                    <div class="dashboard-chart-card__title">Ventas por máquina</div>
                    <div class="dashboard-chart-card__subtitle">Máquinas con mayor facturación.</div>
                </div>
                <span class="badge badge-neutral">Máquina</span>
            </div>
            <div class="dashboard-chart-card__body">
                <div class="chart-rank">
                    @forelse($salesByMachine ?? [] as $machineRow)
                        @php($machineWidth = max(8, (int) round((($machineRow['revenue'] ?? 0) / $salesByMachineMax) * 100)))
                        <div class="chart-rank__row">
                            <div class="chart-rank__head">
                                <div class="chart-rank__title">{{ $machineRow['label'] ?? '—' }}</div>
                                <div class="chart-rank__meta">${{ number_format((float) ($machineRow['revenue'] ?? 0), 0, ',', '.') }}</div>
                            </div>
                            <div class="chart-rank__bar"><span style="width: {{ $machineWidth }}%; --chart-accent:#D71920"></span></div>
                            <div class="chart-rank__foot">{{ $machineRow['location'] ?? 'Sin ubicación' }} · {{ $machineRow['route'] ?? 'Sin ruta' }}</div>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-4) 0">
                            <p style="margin:0">Sin datos por máquina</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    @if(in_array($role, ['admin', 'super_admin'], true) && isset($inventoryNotifications) && $inventoryNotifications->isNotEmpty())
        <div class="panel" style="margin-top:var(--space-5);margin-bottom:var(--space-5);border:1px solid rgba(215,25,32,.22)">
            <div class="panel-header">
                <span class="panel-title">Ajustes pendientes de revisión</span>
                <span class="badge badge-warning">{{ $inventoryNotifications->count() }} nuevas</span>
            </div>
            <div class="panel-body" style="display:grid;gap:var(--space-4)">
                @foreach($inventoryNotifications as $notification)
                    @php($payload = $notification->data)
                    <div style="padding:var(--space-4);border:1px solid var(--gacov-border);border-radius:var(--radius-lg);background:var(--gacov-bg-elevated)">
                        <div style="display:flex;justify-content:space-between;gap:var(--space-3);align-items:flex-start">
                            <div>
                                <strong>{{ $payload['title'] ?? 'Ajuste de inventario pendiente' }}</strong>
                                <p style="margin-top:6px;color:var(--gacov-text-secondary)">{{ $payload['message'] ?? '' }}</p>
                            </div>
                            <span class="badge badge-neutral">{{ $notification->created_at?->diffForHumans() }}</span>
                        </div>
                        @if(!empty($payload['reason']))
                            <p style="margin-top:var(--space-3);font-size:13px;color:var(--gacov-text-secondary)">
                                <strong>Observación:</strong> {{ $payload['reason'] }}
                            </p>
                        @endif
                        <div style="margin-top:var(--space-3)">
                            <a href="{{ $payload['action_url'] ?? route('inventory.movements') }}" class="btn btn-secondary btn-sm" style="width:auto">
                                Revisar movimientos
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-top:var(--space-5)">
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Rutas y conductores</span>
                <span class="badge badge-success">Activas</span>
            </div>
            @if(isset($routes) && $routes->isNotEmpty())
                <table class="data-table dashboard-table-compact">
                    <thead>
                        <tr>
                            <th>Ruta</th>
                            <th>Conductor</th>
                            <th>Placa</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($routes as $r)
                            <tr>
                                <td><strong>{{ $r->code }}</strong> — {{ $r->name }}</td>
                                <td>{{ $r->driver->name ?? '—' }}</td>
                                <td>{{ $r->vehicle_plate ?? '—' }}</td>
                                <td><span class="badge {{ $r->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $r->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="panel-body" style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-8) 0">
                    <p>Sin rutas configuradas</p>
                </div>
            @endif
        </div>

        @can('transfers.view')
            @moduleEnabled('transfers')
            @if(isset($pendingTransfers) && $pendingTransfers->isNotEmpty())
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title">Traslados pendientes de aprobación</span>
                        <a href="{{ route('transfers.index') }}" class="btn btn-secondary btn-sm">Ver todos</a>
                    </div>
                    <table class="data-table dashboard-table-compact">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Creado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingTransfers as $t)
                                <tr>
                                    <td><strong>#{{ $t->id }}</strong></td>
                                    <td>{{ $t->originWarehouse->name ?? '—' }}</td>
                                    <td>{{ $t->destinationWarehouse->name ?? '—' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d/m H:i') }}</td>
                                    <td><a href="{{ route('transfers.show', $t) }}" class="btn btn-primary btn-sm">Revisar</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @endmoduleEnabled
        @endcan
    </div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- CONTADOR — Dashboard financiero                             --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@elseif($isContador)
    <div class="dashboard-kpi-row dashboard-kpi-row--four">
        <div class="kpi-card" style="--kpi-accent:#D71920;--kpi-bg:rgba(215,25,32,.08)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg></div>
            <div class="kpi-value">{{ $stats['total_products'] ?? '—' }}</div>
            <div class="kpi-label">Productos</div>
            <div class="kpi-trend neutral">En bodega</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#111111;--kpi-bg:rgba(17,17,17,.08)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg></div>
            <div class="kpi-value">{{ $stats['total_machines'] ?? '—' }}</div>
            <div class="kpi-label">Máquinas</div>
            <div class="kpi-trend neutral">Activas en campo</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#6B7280;--kpi-bg:rgba(107,114,128,.10)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"/></svg></div>
            <div class="kpi-value">{{ \App\Models\StockMovement::whereMonth('created_at', now()->month)->count() }}</div>
            <div class="kpi-label">Movimientos del mes</div>
            <div class="kpi-trend neutral">{{ now()->translatedFormat('F Y') }}</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#E5E7EB;--kpi-bg:rgba(229,231,235,.65)">
            <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div>
            <div class="kpi-value">{{ \App\Models\TransferOrder::where('status', 'completed')->whereMonth('updated_at', now()->month)->count() }}</div>
            <div class="kpi-label">Traslados completados</div>
            <div class="kpi-trend up">Este mes</div>
        </div>
    </div>

    <div class="dashboard-chart-grid">
        <section class="dashboard-chart-card dashboard-chart-card--wide">
            <div class="dashboard-chart-card__header">
                <div>
                    <div class="dashboard-chart-card__title">Últimos movimientos</div>
                    <div class="dashboard-chart-card__subtitle">Lectura diaria de los últimos 7 días.</div>
                </div>
                <span class="badge badge-neutral">7 días</span>
            </div>
            <div class="dashboard-chart-card__body">
                <div class="chart-bars">
                    @foreach($movementTrend ?? [] as $point)
                        @php($movementHeight = max(8, (int) round((($point['value'] ?? 0) / $movementTrendMax) * 100)))
                        <div class="chart-bars__item">
                            <div class="chart-bars__value">{{ $point['value'] ?? 0 }}</div>
                            <div class="chart-bars__track"><span style="height: {{ $movementHeight }}%; --chart-accent:#D71920"></span></div>
                            <div class="chart-bars__label">{{ $point['label'] ?? '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="dashboard-chart-card dashboard-chart-card--half">
            <div class="dashboard-chart-card__header">
                <div>
                    <div class="dashboard-chart-card__title">Productos más vendidos</div>
                    <div class="dashboard-chart-card__subtitle">Unidades vendidas en los últimos 30 días.</div>
                </div>
                <span class="badge badge-neutral">Top 5</span>
            </div>
            <div class="dashboard-chart-card__body">
                <div class="chart-rank">
                    @foreach($topSoldProducts ?? [] as $product)
                        @php($productWidth = max(8, (int) round((($product['units'] ?? 0) / $topSoldProductsMax) * 100)))
                        <div class="chart-rank__row">
                            <div class="chart-rank__head">
                                <div class="chart-rank__title">{{ filled($product['code'] ?? '') ? ($product['code'] . ' — ') : '' }}{{ $product['label'] ?? '—' }}</div>
                                <div class="chart-rank__meta">{{ number_format((float) ($product['units'] ?? 0), 0, ',', '.') }} un</div>
                            </div>
                            <div class="chart-rank__bar"><span style="width: {{ $productWidth }}%; --chart-accent:#111111"></span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="dashboard-chart-card dashboard-chart-card--third">
            <div class="dashboard-chart-card__header">
                <div>
                    <div class="dashboard-chart-card__title">Ventas por ubicación</div>
                    <div class="dashboard-chart-card__subtitle">Facturación por punto de operación.</div>
                </div>
                <span class="badge badge-neutral">Ubicación</span>
            </div>
            <div class="dashboard-chart-card__body">
                <div class="chart-rank">
                    @foreach($salesByLocation ?? [] as $locationRow)
                        @php($locationWidth = max(8, (int) round((($locationRow['revenue'] ?? 0) / $salesByLocationMax) * 100)))
                        <div class="chart-rank__row">
                            <div class="chart-rank__head">
                                <div class="chart-rank__title">{{ $locationRow['label'] ?? '—' }}</div>
                                <div class="chart-rank__meta">${{ number_format((float) ($locationRow['revenue'] ?? 0), 0, ',', '.') }}</div>
                            </div>
                            <div class="chart-rank__bar"><span style="width: {{ $locationWidth }}%; --chart-accent:#6B7280"></span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="dashboard-chart-card dashboard-chart-card--third">
            <div class="dashboard-chart-card__header">
                <div>
                    <div class="dashboard-chart-card__title">Ventas por máquina</div>
                    <div class="dashboard-chart-card__subtitle">Máquinas con mayor facturación.</div>
                </div>
                <span class="badge badge-neutral">Máquina</span>
            </div>
            <div class="dashboard-chart-card__body">
                <div class="chart-rank">
                    @foreach($salesByMachine ?? [] as $machineRow)
                        @php($machineWidth = max(8, (int) round((($machineRow['revenue'] ?? 0) / $salesByMachineMax) * 100)))
                        <div class="chart-rank__row">
                            <div class="chart-rank__head">
                                <div class="chart-rank__title">{{ $machineRow['label'] ?? '—' }}</div>
                                <div class="chart-rank__meta">${{ number_format((float) ($machineRow['revenue'] ?? 0), 0, ',', '.') }}</div>
                            </div>
                            <div class="chart-rank__bar"><span style="width: {{ $machineWidth }}%; --chart-accent:#D71920"></span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-6);margin-top:var(--space-6)">

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Movimientos del mes</span>
                @can('movements.view')
                @moduleEnabled('reports')
                <a href="{{ route('inventory.movements') }}" class="btn btn-secondary btn-sm">Ver completo</a>
                @endmoduleEnabled
                @endcan
            </div>
            @if(isset($recentMovements) && $recentMovements->isNotEmpty())
            <table class="data-table dashboard-table-compact">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cant.</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentMovements as $m)
                    <tr>
                        <td>{{ $m->product->name ?? '—' }}</td>
                        <td><span class="badge badge-info">{{ str_replace('_', ' ', $m->movement_type) }}</span></td>
                        <td>{{ number_format((float) $m->quantity, 0, ',', '.') }}</td>
                        <td>{{ \Carbon\Carbon::parse($m->created_at)->format('d/m H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="panel-body" style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-10) 0">
                <p>Sin movimientos registrados</p>
            </div>
            @endif
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Accesos rápidos</span>
            </div>
            <div class="panel-body" style="display:flex;flex-direction:column;gap:var(--space-3);padding:var(--space-4)">
                @can('reports.worldoffice')
                <a href="{{ route('worldoffice.index') }}" class="btn btn-primary" style="justify-content:flex-start;gap:var(--space-3)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    Exportar WorldOffice
                </a>
                @endcan
                @can('movements.view')
                @moduleEnabled('reports')
                <a href="{{ route('inventory.movements') }}" class="btn btn-secondary" style="justify-content:flex-start;gap:var(--space-3)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"/></svg>
                    Ver reportes completos
                </a>
                @endmoduleEnabled
                @endcan
                @can('inventory.view')
                @moduleEnabled('inventory')
                <a href="{{ route('inventory.warehouse') }}" class="btn btn-secondary" style="justify-content:flex-start;gap:var(--space-3)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
                    Ver inventario bodega
                </a>
                @endmoduleEnabled
                @endcan
            </div>
        </div>
    </div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- ROL DESCONOCIDO — Dashboard básico                          --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@else

    <div class="kpi-grid">

        <div class="kpi-card" style="--kpi-accent:#D71920;--kpi-bg:rgba(215,25,32,.08)">
            <div class="kpi-icon">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
            </div>
            <div class="kpi-value">{{ $stats['total_products'] ?? '—' }}</div>
            <div class="kpi-label">Productos activos</div>
            <div class="kpi-trend neutral">En bodega principal</div>
        </div>

        <div class="kpi-card" style="--kpi-accent:#111111;--kpi-bg:rgba(17,17,17,.08)">
            <div class="kpi-icon">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
            </div>
            <div class="kpi-value">{{ $stats['total_machines'] ?? '—' }}</div>
            <div class="kpi-label">Máquinas activas</div>
            <div class="kpi-trend neutral">{{ $stats['active_routes'] ?? 0 }} rutas</div>
        </div>

        <div class="kpi-card" style="--kpi-accent:#6B7280;--kpi-bg:rgba(107,114,128,.10)">
            <div class="kpi-icon">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/></svg>
            </div>
            <div class="kpi-value">{{ $stats['movements_today'] ?? 0 }}</div>
            <div class="kpi-label">Movimientos hoy</div>
            <div class="kpi-trend neutral">Traslados y surtidos</div>
        </div>

        <div class="kpi-card" style="--kpi-accent:#E5E7EB;--kpi-bg:rgba(229,231,235,.65)">
            <div class="kpi-icon">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            </div>
            <div class="kpi-value">{{ $stats['low_stock_machines'] ?? 0 }}</div>
            <div class="kpi-label">Máquinas stock bajo</div>
            <div class="kpi-trend {{ ($stats['low_stock_machines'] ?? 0) > 0 ? 'down' : 'up' }}">
                {{ ($stats['low_stock_machines'] ?? 0) > 0 ? 'Requieren surtido' : 'Todo en orden' }}
            </div>
        </div>

    </div>

@endif

@if($isSuperAdmin && isset($projectAudit))
    @include('dashboard.partials.project-status-modal', ['projectAudit' => $projectAudit])
@endif

@endsection
