@extends('layouts.app')
@section('title', 'Bodega Principal')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

@if(!$mainWarehouse)
<div class="alert alert-error">No se encontró bodega principal. Contacta al administrador.</div>
@else

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Inventario central</span>
            <h1 class="inventory-hero__title">Bodega Principal</h1>
            <p class="inventory-hero__subtitle">
                {{ $mainWarehouse->name }}. Punto principal de recepcion y despacho, enfocado en cantidades disponibles por producto.
            </p>
            <div class="inventory-hero__badges">
                <span class="badge badge-info">Bodega: {{ $mainWarehouse->code ?? 'Sin codigo' }}</span>
                <span class="badge badge-neutral">Tipo: {{ ucfirst($mainWarehouse->type ?? 'bodega') }}</span>
                <span class="badge badge-success">Stock activo: {{ number_format($totalSkus, 0, ',', '.') }} SKUs</span>
            </div>
        </div>

        <div class="inventory-hero__actions">
            @can('inventory.adjust')
            <a href="{{ route('inventory.adjust') }}" class="btn btn-primary">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-2.207 2.207L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                Ajuste manual
            </a>
            @endcan
            @can('inventory.load_excel')
            <a href="{{ route('inventory.import.form') }}" class="btn" style="background:#eaf1f7;color:#0f172a">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                Importar carga inicial
            </a>
            @endcan
        </div>
    </div>
</section>

{{-- KPIs --}}
    <div class="kpi-grid" style="margin-bottom:var(--space-8)">
    <div class="kpi-card" style="--kpi-accent:#00D4FF;--kpi-bg:rgba(0,212,255,.1)">
        <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4z"/><path fill-rule="evenodd" d="M3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg></div>
        <div class="kpi-value">{{ $totalSkus }}</div>
        <div class="kpi-label">SKUs con stock</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#7C3AED;--kpi-bg:rgba(124,58,237,.1)">
        <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg></div>
        <div class="kpi-value">{{ number_format($totalUnits, 0, ',', '.') }}</div>
        <div class="kpi-label">Total unidades</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#10B981;--kpi-bg:rgba(16,185,129,.1)">
        <div class="kpi-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v3a2 2 0 002 2h3V3H5zm5 0v7h5a2 2 0 002-2V5a2 2 0 00-2-2h-5zM3 12a2 2 0 012-2h3v7H5a2 2 0 01-2-2v-3zm7 5v-7h5a2 2 0 012 2v3a2 2 0 01-2 2h-5z" clip-rule="evenodd"/></svg></div>
        <div class="kpi-value">{{ number_format($visibleLowStockCount, 0, ',', '.') }}</div>
        <div class="kpi-label">Stock bajo visible</div>
    </div>
</div>

{{-- Filtros --}}
<section class="inventory-filter-card">
    <div class="inventory-filter-card__header">
        <div>
            <div class="inventory-filter-card__title">Filtrar stock de bodega</div>
            <div class="inventory-filter-card__copy">Busca rapido por codigo, nombre o categoria.</div>
        </div>
        <div class="badge badge-neutral">Resultados actuales: {{ number_format($stocks?->count() ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="inventory-filter-card__body">
        <form method="GET" action="{{ route('inventory.warehouse') }}" class="inventory-filter-form" data-server-filter-only>
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
                <label class="form-label">Buscar producto</label>
                <input type="text" name="search" class="form-input" placeholder="Nombre o código..." value="{{ request('search') }}" oninput="clearTimeout(window.__warehouseSearchTimer); window.__warehouseSearchTimer = setTimeout(() => this.form.submit(), 350)">
            </div>
            <div class="form-group" style="min-width:160px;margin-bottom:0">
                <label class="form-label">Categoría</label>
                <select name="category" class="form-input" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="min-width:130px;margin-bottom:0">
                <label class="form-label">Por página</label>
                <select name="per_page" class="form-input" onchange="this.form.submit()">
                    @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inventory-filter-actions">
                <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
                <a href="{{ route('inventory.warehouse') }}" class="inventory-filter-reset">Limpiar</a>
            </div>
        </form>
    </div>
</section>

{{-- Tabla de inventario --}}
<section class="panel inventory-table-panel">
    <div class="inventory-panel-note">
        En esta fase inicial se controla <strong>cantidad disponible por producto</strong>. Los valores o precios no son el foco operativo de esta vista.
    </div>
    <div class="inventory-results-bar">
        <span>Vista actual: <strong>{{ number_format($stocks?->total() ?? 0, 0, ',', '.') }}</strong> productos filtrados</span>
        <span>Total visible en esta página: <strong>{{ number_format($visibleUnits, 0, ',', '.') }}</strong> unidades</span>
    </div>
    @if($stocks && $stocks->count() > 0)
    <div class="table-scroll">
    <table class="data-table" data-server-filter-only>
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th style="text-align:center">Cantidad</th>
                <th>Estado</th>
                @can('inventory.adjust')
                <th></th>
                @endcan
            </tr>
        </thead>
        <tbody>
            @php $totalUnitsTable = 0; @endphp
            @foreach($stocks as $stock)
            @php
                $totalUnitsTable += $stock->quantity;
            @endphp
            @php
                $threshold = max(1, (int) ($stock->min_quantity ?? $stock->product->min_stock_alert ?? 10));
                $stateClass = $stock->quantity <= $threshold
                    ? 'is-low'
                    : ($stock->quantity <= ($threshold * 2) ? 'is-warning' : 'is-good');
                $stateLabel = $stock->quantity <= $threshold
                    ? 'Stock bajo'
                    : ($stock->quantity <= ($threshold * 2) ? 'Atencion' : 'Disponible');
            @endphp
            <tr>
                <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $stock->product->code }}</code></td>
                <td>
                    <div class="inventory-table-product">
                        <span class="inventory-table-product__name">{{ $stock->product->name }}</span>
                        <span class="inventory-table-product__meta">Minimo sugerido: {{ number_format($threshold, 0, ',', '.') }} {{ $stock->product->unit_of_measure }}</span>
                    </div>
                </td>
                <td>
                    @php $cat = $stock->product->category; @endphp
                    <span class="badge {{ $cat === 'snack' ? 'badge-info' : ($cat === 'bebida_fria' ? 'badge-neutral' : ($cat === 'dinero' ? 'badge-success' : 'badge-warning')) }}">
                        {{ str_replace('_',' ', $cat) }}
                    </span>
                </td>
                <td style="color:var(--gacov-text-muted)">{{ $stock->product->unit_of_measure }}</td>
                <td style="text-align:center">
                    <span class="inventory-quantity {{ $stateClass }}">
                        {{ number_format($stock->quantity, 0, ',', '.') }}
                    </span>
                </td>
                <td><span class="inventory-state {{ $stateClass }}">{{ $stateLabel }}</span></td>
                @can('inventory.adjust')
                <td>
                    <a href="{{ route('inventory.adjust', ['product_id' => $stock->product_id]) }}" class="inventory-action-link">
                        Ajustar
                    </a>
                </td>
                @endcan
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:var(--gacov-bg-elevated)">
                <td colspan="4" style="padding:var(--space-3) var(--space-4);font-weight:600;font-size:13px">TOTALES</td>
                <td style="text-align:center;font-weight:700;padding:var(--space-3) var(--space-4)">{{ number_format($totalUnitsTable, 0, ',', '.') }}</td>
                @can('inventory.adjust')<td></td>@endcan
            </tr>
        </tfoot>
    </table>
    </div>
    @if($stocks->hasPages())
    <div class="inventory-pagination">
        <div class="inventory-pagination__meta">
            Mostrando {{ $stocks->firstItem() }}-{{ $stocks->lastItem() }} de {{ $stocks->total() }} productos
        </div>
        <div>{{ $stocks->links() }}</div>
    </div>
    @endif
    @else
    <div class="inventory-empty">
        <p class="inventory-empty__title">Sin inventario visible</p>
        <p>La bodega principal no tiene stock registrado con los filtros actuales.</p>
    </div>
    @endif
</section>
@endif
</div>
@endsection
