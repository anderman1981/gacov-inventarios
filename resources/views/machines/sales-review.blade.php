@extends('layouts.app')
@section('title', 'Revisión de Ventas — Máquinas')

@section('content')
<div class="page-header" style="margin-bottom:var(--space-6)">
    <h1 class="page-title">Revisión de Ventas · Máquinas</h1>
    <p class="page-subtitle">Análisis por ruta, ubicación y máquina — costos, precios, efectivo y categorías</p>
</div>

{{-- ── Filtros ──────────────────────────────────────────────────────── --}}
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6)">
        <form method="GET" action="{{ route('machines.sales.review') }}"
              id="review-filters"
              style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">

            <div class="form-group" style="min-width:220px;margin-bottom:0;flex:1 1 280px">
                <label class="form-label">Buscar</label>
                <input type="search"
                       name="search"
                       value="{{ $search }}"
                       class="form-input"
                       placeholder="Máquina, ruta, código o producto"
                       autocomplete="off">
            </div>

            <div class="form-group" style="min-width:160px;margin-bottom:0">
                <label class="form-label">Ruta</label>
                <select name="route_id" class="form-input" id="filter-route" onchange="this.form.submit()">
                    <option value="">Todas las rutas</option>
                    @foreach($routes as $route)
                    <option value="{{ $route->id }}" {{ $routeId == $route->id ? 'selected' : '' }}>
                        {{ $route->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label class="form-label">Ubicación</label>
                <select name="location" class="form-input" id="filter-location" onchange="this.form.submit()">
                    <option value="">Todas las ubicaciones</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc }}" {{ $location === $loc ? 'selected' : '' }}>
                        {{ $loc }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label class="form-label">Máquina</label>
                <select name="machine_id" class="form-input" onchange="this.form.submit()">
                    <option value="">Todas las máquinas</option>
                    @foreach($machines as $machine)
                    <option value="{{ $machine->id }}" {{ $machineId == $machine->id ? 'selected' : '' }}>
                        {{ $machine->code }} — {{ $machine->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="min-width:120px;margin-bottom:0">
                <label class="form-label">Por página</label>
                <select name="per_page" class="form-input" onchange="this.form.submit()">
                    @foreach([6, 12, 24, 48] as $size)
                    <option value="{{ $size }}" {{ $perPage === $size ? 'selected' : '' }}>
                        {{ $size }}
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

            <button type="submit" class="btn btn-primary" style="width:auto">Aplicar</button>
            <a href="{{ route('machines.sales.review') }}"
               style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px">
               Limpiar
            </a>
        </form>
    </div>
</div>

@if(!empty($summary['by_machine']))
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-header" style="gap:var(--space-3);align-items:flex-start">
        <div>
            <h3 class="panel-title">Revisión por máquina</h3>
            <p class="panel-subtitle" style="font-size:12px;color:var(--gacov-text-muted)">
                Abre una máquina específica para revisar sus ventas como si siguieras la ruta paso a paso.
            </p>
        </div>
        <span class="badge badge-neutral">{{ count($summary['by_machine']) }} máquinas</span>
    </div>
    <div class="review-scroll review-scroll--cards">
        @foreach($summary['by_machine'] as $machine)
        @php
            $machineReviewUrl = request()->fullUrlWithQuery([
                'machine_id' => $machine['id'],
                'page' => 1,
            ]);
            $isSelectedMachine = (int) $machineId === (int) $machine['id'];
        @endphp
        <a href="{{ $machineReviewUrl }}"
           class="machine-review-card {{ $isSelectedMachine ? 'machine-review-card--active' : '' }}">
            <div style="display:flex;justify-content:space-between;gap:var(--space-2);align-items:flex-start">
                <div>
                    <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">
                        {{ $machine['code'] }}
                    </div>
                    <strong style="display:block;font-size:14px;color:var(--gacov-text-primary)">{{ $machine['name'] }}</strong>
                </div>
                <span class="badge badge-info" style="font-size:10px;padding:4px 8px">Ver</span>
            </div>
            <div style="margin-top:var(--space-3);font-size:12px;color:var(--gacov-text-secondary)">{{ $machine['location'] }}</div>
            <div style="margin-top:4px;font-size:12px;color:var(--gacov-text-muted)">{{ $machine['route'] }}</div>
            <div style="display:flex;justify-content:space-between;gap:var(--space-2);align-items:center;margin-top:var(--space-3)">
                <span class="badge badge-neutral" style="font-size:10px">{{ number_format($machine['units'], 0, ',', '.') }} uds</span>
                <strong style="font-size:12px;color:var(--gacov-primary)">${{ number_format($machine['revenue'], 0, ',', '.') }}</strong>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

@if($sales->isEmpty())
<div class="panel">
    <div class="panel-body" style="text-align:center;padding:var(--space-12) 0;color:var(--gacov-text-muted)">
        <svg viewBox="0 0 20 20" fill="currentColor" width="40" height="40" style="margin:0 auto var(--space-4);opacity:.3">
            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
        </svg>
        <p style="font-size:15px;font-weight:500;margin-bottom:var(--space-2)">Sin datos en el período</p>
        <p style="font-size:13px">Ajusta los filtros o el rango de fechas.</p>
    </div>
</div>
@else

{{-- ── Tarjetas KPI ─────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:var(--space-4);margin-bottom:var(--space-6)">
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">Ventas</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-text-primary)">{{ $summary['total_sales'] }}</p>
    </div>
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">Unidades</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-text-primary)">{{ number_format($summary['total_units'], 0, ',', '.') }}</p>
    </div>
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">Ingresos</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-primary)">${{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
    </div>
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">Costo base</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-error)">${{ number_format($summary['total_cost'], 0, ',', '.') }}</p>
    </div>
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">Margen bruto</p>
        @php $marginColor = $summary['gross_margin'] >= 30 ? 'var(--gacov-success)' : ($summary['gross_margin'] >= 15 ? 'var(--gacov-warning)' : 'var(--gacov-error)'); @endphp
        <p style="font-size:22px;font-weight:700;color:{{ $marginColor }}">{{ $summary['gross_margin'] }}%</p>
    </div>
    <div class="panel" style="padding:var(--space-4) var(--space-5)">
        <p style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-2)">Efectivo total</p>
        <p style="font-size:22px;font-weight:700;color:var(--gacov-success)">${{ number_format($summary['cash_total'], 0, ',', '.') }}</p>
        <p style="font-size:11px;color:var(--gacov-text-muted);margin-top:2px">
            B: ${{ number_format($summary['cash_bills'], 0, ',', '.') }} · M: ${{ number_format($summary['cash_coins'], 0, ',', '.') }}
        </p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6)">

    {{-- ── Por categoría ─────────────────────────────────────────────── --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title">Ventas por categoría</h3>
        </div>
        @if(!empty($summary['by_category']))
        <div class="review-scroll review-scroll--table">
        <table class="data-table review-sticky-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th style="text-align:right">Unidades</th>
                    <th style="text-align:right">Costo</th>
                    <th style="text-align:right">Ingresos</th>
                    <th style="text-align:right">Margen</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $catColors = [
                        'cafe'            => '#7C3AED',
                        'suplemento'      => '#F59E0B',
                        'bebida_fria'     => '#00D4FF',
                        'bebida_caliente' => '#EF4444',
                        'snack'           => '#10B981',
                        'insumo'          => '#6B7280',
                        'otro'            => '#374151',
                    ];
                @endphp
                @foreach($summary['by_category'] as $catKey => $cat)
                @php
                    $catMargin = $cat['revenue'] > 0
                        ? round((($cat['revenue'] - $cat['cost']) / $cat['revenue']) * 100, 1)
                        : 0;
                    $catColor = $catColors[$catKey] ?? '#6B7280';
                @endphp
                <tr>
                    <td>
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $catColor }};margin-right:6px;vertical-align:middle"></span>
                        {{ $cat['label'] }}
                    </td>
                    <td style="text-align:right">{{ number_format($cat['units'], 0, ',', '.') }}</td>
                    <td style="text-align:right;font-size:12px;color:var(--gacov-error)">${{ number_format($cat['cost'], 0, ',', '.') }}</td>
                    <td style="text-align:right;font-weight:600;color:var(--gacov-primary)">${{ number_format($cat['revenue'], 0, ',', '.') }}</td>
                    <td style="text-align:right;font-size:12px;color:{{ $catMargin >= 30 ? 'var(--gacov-success)' : ($catMargin >= 15 ? 'var(--gacov-warning)' : 'var(--gacov-error)') }}">
                        {{ $catMargin }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @else
        <div class="panel-body" style="text-align:center;padding:var(--space-6);color:var(--gacov-text-muted);font-size:13px">Sin ítems vendidos.</div>
        @endif
    </div>

    {{-- ── Por máquina ───────────────────────────────────────────────── --}}
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title">Ventas por máquina</h3>
        </div>
        @if(!empty($summary['by_machine']))
        <div class="review-scroll review-scroll--table">
        <table class="data-table review-sticky-table">
            <thead>
                <tr>
                    <th>Máquina</th>
                    <th>Ubicación / Ruta</th>
                    <th style="text-align:right">Uds.</th>
                    <th style="text-align:right">Ingresos</th>
                    <th style="text-align:right">Efectivo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['by_machine'] as $machine)
                <tr>
                    <td style="font-weight:600">
                        <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">{{ $machine['code'] }}</div>
                        {{ $machine['name'] }}
                    </td>
                    <td>
                        <span style="font-size:12px;color:var(--gacov-text-secondary)">{{ $machine['location'] }}</span>
                        <br>
                        <span class="badge badge-info" style="font-size:10px">{{ $machine['route'] }}</span>
                        @if($machineId === $machine['id'])
                        <span class="badge badge-neutral" style="font-size:10px;margin-left:4px">Actual</span>
                        @endif
                    </td>
                    <td style="text-align:right">{{ number_format($machine['units'], 0, ',', '.') }}</td>
                    <td style="text-align:right;font-weight:600;color:var(--gacov-primary)">${{ number_format($machine['revenue'], 0, ',', '.') }}</td>
                    <td style="text-align:right">
                        <span style="font-size:12px;color:var(--gacov-success)">${{ number_format($machine['cash_total'], 0, ',', '.') }}</span>
                        @if($machine['cash_coins'] > 0)
                        <br><span style="font-size:10px;color:var(--gacov-warning)">M: ${{ number_format($machine['cash_coins'], 0, ',', '.') }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @else
        <div class="panel-body" style="text-align:center;padding:var(--space-6);color:var(--gacov-text-muted);font-size:13px">Sin datos por máquina.</div>
        @endif
    </div>

</div>

{{-- ── Detalle por producto ──────────────────────────────────────────── --}}
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-header">
        <h3 class="panel-title">Detalle por producto</h3>
        <p class="panel-subtitle" style="font-size:12px;color:var(--gacov-text-muted)">Precio promedio real registrado en la venta (puede variar por ubicación)</p>
    </div>
    @if(!empty($summary['by_product']))
    <div class="review-scroll review-scroll--table review-scroll--tall">
    <table class="data-table review-sticky-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th style="text-align:right">Unidades</th>
                <th style="text-align:right">Costo unit.</th>
                <th style="text-align:right">Precio prom.</th>
                <th style="text-align:right">Diferencia</th>
                <th style="text-align:right">Ingresos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['by_product'] as $prod)
            @php
                $diff = $prod['avg_price'] - $prod['cost_unit'];
                $diffColor = $diff >= 0 ? 'var(--gacov-success)' : 'var(--gacov-error)';
            @endphp
            <tr>
                <td style="font-weight:600">{{ $prod['name'] }}</td>
                <td>
                    <span class="badge badge-neutral" style="font-size:11px">{{ $prod['category'] }}</span>
                </td>
                <td style="text-align:right">{{ number_format($prod['units'], 0, ',', '.') }}</td>
                <td style="text-align:right;font-size:13px;color:var(--gacov-error)">
                    ${{ number_format($prod['cost_unit'], 0, ',', '.') }}
                </td>
                <td style="text-align:right;font-size:13px;color:var(--gacov-primary)">
                    ${{ number_format($prod['avg_price'], 0, ',', '.') }}
                </td>
                <td style="text-align:right;font-size:13px;color:{{ $diffColor }}">
                    {{ $diff >= 0 ? '+' : '' }}${{ number_format($diff, 0, ',', '.') }}
                </td>
                <td style="text-align:right;font-weight:600;color:var(--gacov-primary)">
                    ${{ number_format($prod['revenue'], 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-6);color:var(--gacov-text-muted);font-size:13px">Sin productos vendidos en el período.</div>
    @endif
</div>

{{-- ── Listado de ventas ─────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <div>
            <h3 class="panel-title">Ventas del período</h3>
            <p class="panel-subtitle" style="font-size:12px;color:var(--gacov-text-muted)">
                Mostrando {{ $sales->firstItem() ?? 0 }} - {{ $sales->lastItem() ?? 0 }} de {{ number_format($sales->total(), 0, ',', '.') }} registros.
            </p>
        </div>
        <span class="badge badge-neutral">{{ $sales->count() }} en esta página</span>
    </div>
    <div class="review-scroll review-scroll--table review-scroll--tall">
    <table class="data-table review-sticky-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Máquina</th>
                <th>Ubicación</th>
                <th>Ruta</th>
                <th style="text-align:right">Uds.</th>
                <th style="text-align:right">Ingresos</th>
                <th style="text-align:right">Billetes</th>
                <th style="text-align:right">Monedas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            <tr>
                <td style="font-size:13px;color:var(--gacov-text-secondary);white-space:nowrap">
                    {{ $sale->sale_date->format('d/m/Y') }}
                </td>
                <td style="font-weight:600">{{ $sale->machine?->name ?? '—' }}</td>
                <td style="font-size:12px;color:var(--gacov-text-secondary)">{{ $sale->machine?->location ?? '—' }}</td>
                <td>
                    @if($sale->machine?->route)
                    <span class="badge badge-info" style="font-size:11px">{{ $sale->machine->route->name }}</span>
                    @else
                    <span style="color:var(--gacov-text-muted)">—</span>
                    @endif
                </td>
                <td style="text-align:right">{{ $sale->totalUnits() }}</td>
                <td style="text-align:right;font-weight:600;color:var(--gacov-primary)">${{ number_format($sale->totalRevenue(), 0, ',', '.') }}</td>
                <td style="text-align:right;font-size:12px;color:var(--gacov-success)">${{ number_format($sale->cash_bills, 0, ',', '.') }}</td>
                <td style="text-align:right;font-size:12px;color:var(--gacov-warning)">${{ number_format($sale->cash_coins, 0, ',', '.') }}</td>
                <td>
                    <span class="badge {{ $sale->status === 'completed' ? 'badge-success' : 'badge-neutral' }}" style="font-size:11px">
                        {{ $sale->status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    @if($sales->hasPages())
    <div class="inventory-pagination">
        <div class="inventory-pagination__meta">
            Página {{ $sales->currentPage() }} de {{ $sales->lastPage() }}
        </div>
        <div>{{ $sales->withQueryString()->links() }}</div>
    </div>
    @endif
</div>

@endif {{-- /sales->isEmpty --}}
@push('styles')
<style>
    .review-scroll {
        overflow: auto;
        border-top: 1px solid var(--gacov-border);
    }
    .review-scroll--cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: var(--space-3);
        padding: var(--space-4) var(--space-6) var(--space-6);
        max-height: 280px;
    }
    .review-scroll--table {
        max-height: 360px;
    }
    .review-scroll--tall {
        max-height: 460px;
    }
    .review-sticky-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--gacov-surface);
        box-shadow: 0 1px 0 var(--gacov-border);
    }
    .machine-review-card {
        display: block;
        border: 1px solid var(--gacov-border);
        border-radius: 18px;
        background: var(--gacov-surface);
        padding: var(--space-4);
        text-decoration: none;
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        box-shadow: var(--gacov-shadow-sm);
    }
    .machine-review-card:hover {
        transform: translateY(-1px);
        border-color: var(--gacov-primary);
        box-shadow: var(--gacov-shadow-md);
    }
    .machine-review-card--active {
        border-color: var(--gacov-primary);
        box-shadow: 0 0 0 1px rgba(0, 212, 255, 0.15), var(--gacov-shadow-md);
    }
    .machine-review-card--active strong {
        color: var(--gacov-primary);
    }
    @media (max-width: 1024px) {
        .machine-review-card {
            padding: var(--space-3);
        }
        .review-scroll--cards {
            max-height: 240px;
        }
    }
    @media (max-width: 768px) {
        .machine-review-card {
            border-radius: 14px;
        }
        .review-scroll--cards {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            max-height: 220px;
        }
        .review-scroll--table {
            max-height: 320px;
        }
        .review-scroll--tall {
            max-height: 360px;
        }
    }
</style>
@endpush
@endsection
