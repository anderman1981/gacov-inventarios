@extends('layouts.app')
@section('title', 'Movimientos')

@section('content')
<div class="page-header">
    <h1 class="page-title">Historial de movimientos</h1>
    <p class="page-subtitle">Registro completo de entradas, salidas y traslados</p>
</div>

{{-- Filtros --}}
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6)">
        <form method="GET" action="{{ route('inventory.movements') }}" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="flex:1;min-width:180px;margin-bottom:0">
                <label class="form-label">Producto</label>
                <input type="text" name="search" class="form-input" placeholder="Nombre del producto..." value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label class="form-label">Tipo de movimiento</label>
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
            <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
            <a href="{{ route('inventory.movements') }}" style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px">Limpiar</a>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="panel">
    @if($movements->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Tipo</th>
                <th style="text-align:center">Cantidad</th>
                <th>Bodega origen</th>
                <th>Bodega destino</th>
                <th>Usuario</th>
                <th>Referencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $movement)
            <tr>
                <td style="white-space:nowrap;color:var(--gacov-text-secondary);font-size:13px">
                    {{ $movement->created_at->format('d/m/Y H:i') }}
                </td>
                <td><strong>{{ $movement->product->name ?? '—' }}</strong></td>
                <td>
                    @php
                    $typeBadge = match($movement->movement_type) {
                        'carga_inicial'    => ['badge-info', 'Carga inicial'],
                        'ajuste_manual'    => ['badge-warning', 'Ajuste manual'],
                        'traslado_salida'  => ['badge-neutral', 'Traslado salida'],
                        'traslado_entrada' => ['badge-neutral', 'Traslado entrada'],
                        'surtido_maquina'  => ['badge-success', 'Surtido máquina'],
                        'venta_maquina'    => ['', 'Venta máquina'],
                        'conteo_fisico'    => ['badge-warning', 'Conteo físico'],
                        'exportado_wo'     => ['badge-info', 'Exportado WO'],
                        default            => ['badge-neutral', $movement->movement_type],
                    };
                    @endphp
                    @if($movement->movement_type === 'venta_maquina')
                        <span class="badge" style="background:rgba(124,58,237,.12);color:#7C3AED">Venta máquina</span>
                    @else
                        <span class="badge {{ $typeBadge[0] }}">{{ $typeBadge[1] }}</span>
                    @endif
                </td>
                <td style="text-align:center;font-weight:700">{{ number_format((float) $movement->quantity, 0, ',', '.') }}</td>
                <td style="font-size:13px;color:var(--gacov-text-secondary)">{{ $movement->fromWarehouse->name ?? '—' }}</td>
                <td style="font-size:13px;color:var(--gacov-text-secondary)">{{ $movement->toWarehouse->name ?? '—' }}</td>
                <td style="font-size:13px">{{ $movement->user->name ?? '—' }}</td>
                <td style="font-size:12px;color:var(--gacov-text-muted);white-space:nowrap">{{ $movement->reference ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:var(--space-4) var(--space-6);border-top:1px solid var(--gacov-border)">
        {{ $movements->links() }}
    </div>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-12) 0;color:var(--gacov-text-muted)">
        <p style="font-size:15px;font-weight:500">Sin movimientos</p>
        <p style="font-size:13px">No se encontraron movimientos con los filtros aplicados.</p>
    </div>
    @endif
</div>
@endsection
