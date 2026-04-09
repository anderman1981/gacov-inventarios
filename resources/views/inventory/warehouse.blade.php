@extends('layouts.app')
@section('title', 'Bodega Principal')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <h1 class="page-title">Bodega Principal</h1>
        <p class="page-subtitle">
            {{ $mainWarehouse ? $mainWarehouse->name : 'Sin bodega configurada' }}
            @if($mainWarehouse)
            · Fase inicial enfocada en cantidades por producto
            @endif
        </p>
    </div>
    <div style="display:flex;gap:var(--space-3)">
        @can('inventory.adjust')
        <a href="{{ route('inventory.adjust') }}" class="btn btn-primary" style="width:auto">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-2.207 2.207L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
            Ajuste manual
        </a>
        @endcan
        @can('inventory.load_excel')
        <a href="{{ route('inventory.import.form') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
            Importar carga inicial
        </a>
        @endcan
    </div>
</div>

@if(!$mainWarehouse)
<div class="alert alert-error">No se encontró bodega principal. Contacta al administrador.</div>
@else

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
        <div class="kpi-value">{{ $stocks->where('quantity', '<=', 10)->count() }}</div>
        <div class="kpi-label">Productos con stock bajo</div>
    </div>
</div>

{{-- Filtros --}}
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6)">
        <form method="GET" action="{{ route('inventory.warehouse') }}" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
                <label class="form-label">Buscar producto</label>
                <input type="text" name="search" class="form-input" placeholder="Nombre o código..." value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:160px;margin-bottom:0">
                <label class="form-label">Categoría</label>
                <select name="category" class="form-input">
                    <option value="">Todas</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
            <a href="{{ route('inventory.warehouse') }}" style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px">Limpiar</a>
        </form>
    </div>
</div>

{{-- Tabla de inventario --}}
<div class="panel">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6);border-bottom:1px solid var(--gacov-border);color:var(--gacov-text-secondary)">
        En esta fase inicial se controla <strong>cantidad disponible por producto</strong>. Los valores o precios no son el foco operativo de esta vista.
    </div>
    @if($stocks->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th style="text-align:center">Cantidad</th>
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
            <tr>
                <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $stock->product->code }}</code></td>
                <td><strong>{{ $stock->product->name }}</strong></td>
                <td>
                    @php $cat = $stock->product->category; @endphp
                    <span class="badge {{ $cat === 'snack' ? 'badge-info' : ($cat === 'bebida_fria' ? 'badge-neutral' : 'badge-warning') }}">
                        {{ str_replace('_',' ', $cat) }}
                    </span>
                </td>
                <td style="color:var(--gacov-text-muted)">{{ $stock->product->unit_of_measure }}</td>
                <td style="text-align:center">
                    <span style="font-weight:700;color:{{ $stock->quantity < 10 ? 'var(--gacov-error)' : ($stock->quantity < 30 ? 'var(--gacov-warning)' : 'var(--gacov-success)') }}">
                        {{ number_format($stock->quantity, 0, ',', '.') }}
                    </span>
                </td>
                @can('inventory.adjust')
                <td>
                    <a href="{{ route('inventory.adjust', ['product_id' => $stock->product_id]) }}"
                       style="padding:4px 10px;background:rgba(0,212,255,.1);color:var(--gacov-primary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
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
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-12) 0;color:var(--gacov-text-muted)">
        <p style="font-size:15px;font-weight:500;margin-bottom:var(--space-2)">Sin inventario</p>
        <p style="font-size:13px">La bodega principal no tiene stock registrado.</p>
    </div>
    @endif
</div>
@endif
@endsection
