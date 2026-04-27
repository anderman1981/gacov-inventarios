@extends('layouts.app')
@section('title', 'Traslados')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4);margin-bottom:var(--space-6)">
    <div>
        <h1 class="page-title">Órdenes de traslado</h1>
        <p class="page-subtitle">Gestión de movimientos entre bodegas y vehículos</p>
    </div>
    @can('transfers.create')
    <a href="{{ route('transfers.create') }}" class="btn btn-primary" style="width:auto">
        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Nuevo traslado
    </a>
    @endcan
</div>

{{-- Filtros --}}
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6)">
        <form method="GET" action="{{ route('transfers.index') }}" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
                <label class="form-label">Buscar por código</label>
                <input type="text" name="search" class="form-input" placeholder="Ej: TRAS-20240101-0001" value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label class="form-label">Estado</label>
                <select name="status" class="form-input">
                    <option value="">Todos los estados</option>
                    @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
            <a href="{{ route('transfers.index') }}" style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px;align-self:flex-end">Limpiar</a>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="panel">
    @if($transfers->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Origen</th>
                <th>Destino</th>
                <th style="text-align:center">Estado</th>
                <th>Solicitado por</th>
                <th>Fecha</th>
                <th style="text-align:right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfers as $transfer)
            @php
                $badgeClass = match($transfer->status) {
                    'pendiente'  => 'badge-warning',
                    'aprobado'   => 'badge-info',
                    'completado' => 'badge-success',
                    'cancelado'  => 'badge-neutral',
                    'borrador'   => 'badge-neutral',
                    default      => 'badge-neutral',
                };
                $statusLabel = match($transfer->status) {
                    'pendiente'  => 'Pendiente',
                    'aprobado'   => 'Aprobada',
                    'completado' => 'Completada',
                    'cancelado'  => 'Cancelada',
                    'borrador'   => 'Borrador',
                    default      => $transfer->status,
                };
            @endphp
            <tr>
                <td>
                    <a href="{{ route('transfers.show', $transfer) }}"
                       style="color:var(--gacov-primary);text-decoration:none;font-weight:600;font-size:13px">
                        {{ $transfer->code }}
                    </a>
                </td>
                <td style="color:var(--gacov-text-secondary)">
                    {{ $transfer->originWarehouse?->name ?? '—' }}
                </td>
                <td style="color:var(--gacov-text-secondary)">
                    {{ $transfer->destinationWarehouse?->name ?? '—' }}
                </td>
                <td style="text-align:center">
                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                </td>
                <td style="color:var(--gacov-text-muted);font-size:13px">
                    {{ $transfer->requestedBy?->name ?? '—' }}
                </td>
                <td style="color:var(--gacov-text-muted);font-size:13px">
                    {{ $transfer->created_at->format('d/m/Y H:i') }}
                </td>
                <td style="text-align:right">
                    <div style="display:flex;gap:var(--space-2);justify-content:flex-end;flex-wrap:wrap">
                        <a href="{{ route('transfers.show', $transfer) }}"
                           style="padding:4px 10px;background:rgba(0,212,255,.1);color:var(--gacov-primary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                            Ver
                        </a>

                        @if($transfer->status === 'pendiente')
                            @can('transfers.approve')
                            <form method="POST" action="{{ route('transfers.approve', $transfer) }}" style="display:inline" onsubmit="return confirm('¿Aprobar esta orden de traslado?')">
                                @csrf
                                <button type="submit"
                                    style="padding:4px 10px;background:rgba(16,185,129,.15);color:var(--gacov-success);border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer">
                                    Aprobar
                                </button>
                            </form>
                            @endcan

                            @can('transfers.create')
                            <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" style="display:inline" onsubmit="return confirm('¿Cancelar esta orden de traslado?')">
                                @csrf
                                <button type="submit"
                                    style="padding:4px 10px;background:rgba(239,68,68,.12);color:var(--gacov-error);border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer">
                                    Cancelar
                                </button>
                            </form>
                            @endcan
                        @endif

                        @if($transfer->status === 'aprobado')
                            @can('transfers.complete')
                            <a href="{{ route('transfers.show', $transfer) }}#completar"
                               style="padding:4px 10px;background:rgba(124,58,237,.15);color:var(--gacov-secondary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                                Completar
                            </a>
                            @endcan
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Paginación --}}
    @if($transfers->hasPages())
    <div style="padding:var(--space-4) var(--space-6);border-top:1px solid var(--gacov-border)">
        {{ $transfers->links() }}
    </div>
    @endif

    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-12) 0;color:var(--gacov-text-muted)">
        <svg viewBox="0 0 20 20" fill="currentColor" width="48" height="48" style="opacity:.3;margin-bottom:var(--space-4)">
            <path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/>
        </svg>
        <p style="font-size:15px;font-weight:500;margin-bottom:var(--space-2)">Sin órdenes de traslado</p>
        <p style="font-size:13px">
            @if(request('search') || request('status'))
                No se encontraron resultados con los filtros aplicados.
            @else
                Aún no se han creado órdenes de traslado.
            @endif
        </p>
        @can('transfers.create')
        <a href="{{ route('transfers.create') }}" class="btn btn-primary" style="width:auto;margin-top:var(--space-4)">
            Crear primera orden
        </a>
        @endcan
    </div>
    @endif
</div>
@endsection
