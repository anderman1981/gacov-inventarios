@extends('layouts.app')
@section('title', 'Movimientos')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Trazabilidad</span>
            <h1 class="inventory-hero__title">Historial de movimientos</h1>
            <p class="inventory-hero__subtitle">Registro consolidado de entradas, salidas, ajustes, surtidos, ventas y traslados.</p>
            <div class="inventory-hero__badges">
                <span class="badge badge-info">Registros: {{ number_format($movements->total(), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</section>

<section class="inventory-filter-card">
    <div class="inventory-filter-card__header">
        <div>
            <div class="inventory-filter-card__title">Filtrar historial</div>
            <div class="inventory-filter-card__copy">Reduce ruido por producto, tipo de movimiento o rango de fechas.</div>
        </div>
    </div>
    <div class="inventory-filter-card__body">
        <form method="GET" action="{{ route('inventory.movements') }}" class="inventory-filter-form">
            <div class="form-group" style="flex:1;min-width:180px;margin-bottom:0">
                <label class="form-label">Producto</label>
                <input type="text" name="search" class="form-input" placeholder="Nombre del producto..." value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label class="form-label">Tipo</label>
                <select name="type" class="form-input">
                    <option value="">Todos</option>
                    @foreach($movementTypes as $key => $label)
                    <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Desde</label>
                <input type="date" name="from" class="form-input" value="{{ request('from') }}">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Hasta</label>
                <input type="date" name="to" class="form-input" value="{{ request('to') }}">
            </div>
            <div class="form-group" style="min-width:130px;margin-bottom:0">
                <label class="form-label">Por página</label>
                <select name="per_page" class="form-input">
                    @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inventory-filter-actions">
                <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
                <a href="{{ route('inventory.movements') }}" class="inventory-filter-reset">Limpiar</a>
            </div>
        </form>
    </div>
</section>

<section class="panel inventory-table-panel">
    <div class="inventory-results-bar">
        <span>Movimientos encontrados: <strong>{{ number_format($movements->total(), 0, ',', '.') }}</strong></span>
        <span>En esta página: <strong>{{ number_format($movements->count(), 0, ',', '.') }}</strong></span>
    </div>

    @if($movements->count() > 0)
    <div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Tipo</th>
                <th style="text-align:center">Cantidad</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Usuario</th>
                <th>Referencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $movement)
            <tr>
                <td style="white-space:nowrap;font-size:13px">
                    <div class="inventory-table-product">
                        <span class="inventory-table-product__name">{{ $movement->created_at->format('d/m/Y') }}</span>
                        <span class="inventory-table-product__meta">{{ $movement->created_at->format('H:i') }}</span>
                    </div>
                </td>
                <td>
                    <div class="inventory-table-product">
                        <span class="inventory-table-product__name">{{ $movement->product->name ?? '—' }}</span>
                        <span class="inventory-table-product__meta">{{ $movement->product->code ?? '—' }}</span>
                    </div>
                </td>
                <td>
                    @php
                    $typeBadge = match($movement->movement_type) {
                        'carga_inicial'    => ['badge-info', 'Carga inicial'],
                        'ajuste_manual'    => ['badge-warning', 'Ajuste manual'],
                        'traslado_salida'  => ['badge-neutral', 'Traslado salida'],
                        'traslado_entrada' => ['badge-neutral', 'Traslado entrada'],
                        'surtido_maquina'  => ['badge-success', 'Surtido máquina'],
                        'venta_maquina'    => ['badge-info', 'Venta máquina'],
                        'conteo_fisico'    => ['badge-warning', 'Conteo físico'],
                        'exportado_wo'     => ['badge-info', 'Exportado WO'],
                        default            => ['badge-neutral', $movement->movement_type],
                    };
                    @endphp
                    <span class="badge {{ $typeBadge[0] }}">{{ $typeBadge[1] }}</span>
                </td>
                <td style="text-align:center"><span class="inventory-quantity is-good">{{ number_format((float) $movement->quantity, 0, ',', '.') }}</span></td>
                <td>{{ $movement->fromWarehouse->name ?? '—' }}</td>
                <td>{{ $movement->toWarehouse->name ?? '—' }}</td>
                <td>{{ $movement->user->name ?? '—' }}</td>
                <td style="font-size:12px;color:var(--gacov-text-muted);white-space:nowrap">{{ $movement->reference ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    @if($movements->hasPages())
    <div class="inventory-pagination">
        <div class="inventory-pagination__meta">
            Mostrando {{ $movements->firstItem() }}-{{ $movements->lastItem() }} de {{ $movements->total() }} movimientos
        </div>
        <div>{{ $movements->links() }}</div>
    </div>
    @endif
    @else
    <div class="inventory-empty">
        <p class="inventory-empty__title">Sin movimientos</p>
        <p>No se encontraron movimientos con los filtros aplicados.</p>
    </div>
    @endif
</section>
</div>
@endsection
