@extends('layouts.app')
@section('title', $machine->name)

@section('content')
{{-- Encabezado --}}
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-2)">
            <code style="font-size:13px;color:var(--gacov-primary);background:rgba(0,212,255,.08);padding:2px 8px;border-radius:var(--radius-sm)">{{ $machine->code }}</code>
            <span class="badge {{ $machine->is_active ? 'badge-success' : 'badge-neutral' }}">
                {{ $machine->is_active ? 'Activa' : 'Inactiva' }}
            </span>
        </div>
        <h1 class="page-title">{{ $machine->name }}</h1>
        <p class="page-subtitle">
            <a href="{{ route('machines.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">Máquinas</a> / {{ $machine->name }}
        </p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        @can('machines.edit')
        <a href="{{ route('machines.edit', $machine) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
            Editar máquina
        </a>
        @endcan
        @can('stockings.create')
        <a href="{{ route('driver.stocking.create') }}?machine_id={{ $machine->id }}" class="btn btn-primary" style="width:auto">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg>
            Surtir esta máquina
        </a>
        @endcan
    </div>
</div>

{{-- Contenido principal: 2 columnas --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6)">

    {{-- Columna izquierda: Info general --}}
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">Información general</span>
        </div>
        <div class="panel-body">
            <table style="width:100%;border-collapse:collapse">
                <tbody>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td style="padding:var(--space-3) 0;color:var(--gacov-text-muted);font-size:13px;width:40%">Código</td>
                        <td style="padding:var(--space-3) 0;font-weight:600">
                            <code style="color:var(--gacov-primary)">{{ $machine->code }}</code>
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td style="padding:var(--space-3) 0;color:var(--gacov-text-muted);font-size:13px">Código WO</td>
                        <td style="padding:var(--space-3) 0">{{ $machine->worldoffice_code ?? '—' }}</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td style="padding:var(--space-3) 0;color:var(--gacov-text-muted);font-size:13px">Tipo</td>
                        <td style="padding:var(--space-3) 0">{{ $machine->type ?? '—' }}</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td style="padding:var(--space-3) 0;color:var(--gacov-text-muted);font-size:13px">Ubicación</td>
                        <td style="padding:var(--space-3) 0">{{ $machine->location ?? '—' }}</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td style="padding:var(--space-3) 0;color:var(--gacov-text-muted);font-size:13px">Ruta</td>
                        <td style="padding:var(--space-3) 0">
                            @if($machine->route)
                            <span class="badge badge-info">{{ $machine->route->name }}</span>
                            @else
                            <span style="color:var(--gacov-text-muted)">Sin ruta asignada</span>
                            @endif
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--gacov-border)">
                        <td style="padding:var(--space-3) 0;color:var(--gacov-text-muted);font-size:13px">Operador</td>
                        <td style="padding:var(--space-3) 0">
                            {{ $machine->operator?->name ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:var(--space-3) 0;color:var(--gacov-text-muted);font-size:13px">Bodega</td>
                        <td style="padding:var(--space-3) 0">
                            @if($warehouse)
                            <code style="font-size:12px;color:var(--gacov-text-secondary)">{{ $warehouse->code }}</code>
                            @else
                            <span style="color:var(--gacov-error);font-size:13px">Sin bodega asociada</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Columna derecha: Stock actual --}}
    <div class="panel">
        <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between">
            <span class="panel-title">Stock actual</span>
            <span style="font-size:13px;color:var(--gacov-text-muted)">
                Total: <strong style="color:var(--gacov-text-primary)">{{ number_format((float) $stockItems->sum('quantity'), 0, ',', '.') }}</strong> uds.
            </span>
        </div>
        @if($stockItems->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:right">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockItems->sortByDesc('quantity') as $item)
                @php
                $qty = $item->quantity;
                $qtyColor = match(true) {
                    $qty < 10  => 'var(--gacov-error)',
                    $qty < 30  => 'var(--gacov-warning)',
                    default    => 'var(--gacov-success)',
                };
                @endphp
                <tr>
                    <td style="font-size:13px">
                        {{ $item->product?->name ?? 'Producto eliminado' }}
                        @if($item->product?->sku)
                        <span style="display:block;font-size:11px;color:var(--gacov-text-muted)">{{ $item->product->sku }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-weight:700;font-size:15px;color:{{ $qtyColor }}">
                        {{ number_format((float) $qty, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="panel-body" style="text-align:center;padding:var(--space-8) 0;color:var(--gacov-text-muted)">
            <p style="font-size:13px">Sin stock registrado en esta máquina.</p>
        </div>
        @endif
    </div>
</div>

{{-- Últimos surtidos --}}
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-header">
        <span class="panel-title">Últimos surtidos</span>
    </div>
    @if($recentStockings->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha</th>
                <th>Conductor</th>
                <th style="text-align:center">Items</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentStockings as $stocking)
            <tr>
                <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $stocking->code }}</code></td>
                <td style="color:var(--gacov-text-secondary);font-size:13px">
                    {{ $stocking->created_at->format('d/m/Y H:i') }}
                </td>
                <td style="font-size:13px">{{ $stocking->user?->name ?? '—' }}</td>
                <td style="text-align:center;font-weight:600">{{ $stocking->items_count }}</td>
                <td>
                    @php
                    $statusBadge = match($stocking->status) {
                        'completado' => 'badge-success',
                        'pendiente'  => 'badge-warning',
                        'cancelado'  => 'badge-error',
                        default      => 'badge-neutral',
                    };
                    @endphp
                    <span class="badge {{ $statusBadge }}">{{ ucfirst($stocking->status) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-8) 0;color:var(--gacov-text-muted)">
        <p style="font-size:13px">No hay surtidos registrados para esta máquina.</p>
    </div>
    @endif
</div>

{{-- Últimas ventas --}}
<div class="panel">
    <div class="panel-header">
        <span class="panel-title">Últimas ventas</span>
    </div>
    @if($recentSales->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha venta</th>
                <th>Registrado por</th>
                <th style="text-align:center">Uds. vendidas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentSales as $sale)
            <tr>
                <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $sale->code }}</code></td>
                <td style="color:var(--gacov-text-secondary);font-size:13px">
                    {{ $sale->sale_date?->format('d/m/Y') ?? $sale->created_at->format('d/m/Y') }}
                </td>
                <td style="font-size:13px">{{ $sale->user?->name ?? '—' }}</td>
                <td style="text-align:center;font-weight:600">{{ number_format((float) ($sale->total_units ?? 0), 0, ',', '.') }}</td>
                <td>
                    @php
                    $saleBadge = match($sale->status) {
                        'exportado'  => 'badge-success',
                        'pendiente'  => 'badge-warning',
                        'cancelado'  => 'badge-error',
                        default      => 'badge-neutral',
                    };
                    @endphp
                    <span class="badge {{ $saleBadge }}">{{ ucfirst($sale->status) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-8) 0;color:var(--gacov-text-muted)">
        <p style="font-size:13px">No hay ventas registradas para esta máquina.</p>
    </div>
    @endif
</div>
@endsection
