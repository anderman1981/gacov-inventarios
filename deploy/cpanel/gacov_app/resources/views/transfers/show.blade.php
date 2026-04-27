@extends('layouts.app')
@section('title', 'Traslado ' . $transfer->code)

@section('content')
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

{{-- Encabezado --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4);margin-bottom:var(--space-6)">
    <div>
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-2)">
            <a href="{{ route('transfers.index') }}" style="color:var(--gacov-text-muted);text-decoration:none;font-size:13px">
                ← Traslados
            </a>
        </div>
        <div style="display:flex;align-items:center;gap:var(--space-3);flex-wrap:wrap">
            <h1 class="page-title" style="margin:0">{{ $transfer->code }}</h1>
            <span class="badge {{ $badgeClass }}" style="font-size:13px;padding:6px 14px">{{ $statusLabel }}</span>
        </div>
        <p class="page-subtitle" style="margin-top:var(--space-2)">
            Creada el {{ $transfer->created_at->format('d/m/Y \a \l\a\s H:i') }}
        </p>
    </div>

    {{-- Acciones de cabecera --}}
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        @if($transfer->status === 'pendiente')
            @can('transfers.approve')
            <form method="POST" action="{{ route('transfers.approve', $transfer) }}" onsubmit="return confirm('¿Aprobar esta orden de traslado?')">
                @csrf
                <button type="submit" class="btn btn-primary" style="width:auto;background:var(--gacov-success)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Aprobar
                </button>
            </form>
            @endcan

            @can('transfers.create')
            <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" onsubmit="return confirm('¿Cancelar esta orden de traslado? Esta acción no se puede deshacer.')">
                @csrf
                <button type="submit" class="btn" style="width:auto;background:rgba(239,68,68,.12);color:var(--gacov-error);border:1px solid rgba(239,68,68,.2)">
                    Cancelar orden
                </button>
            </form>
            @endcan
        @endif
    </div>
</div>

{{-- Errores --}}
@if($errors->any())
<div class="alert alert-error" style="margin-bottom:var(--space-5)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div>
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5)">

    {{-- Información del traslado --}}
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Información del traslado</h2>
        </div>
        <div class="panel-body">
            <div style="display:grid;gap:var(--space-4)">
                <div>
                    <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-1)">Bodega origen</div>
                    <div style="font-weight:600;color:var(--gacov-text-primary)">
                        {{ $transfer->originWarehouse?->name ?? '—' }}
                    </div>
                    @if($transfer->originWarehouse)
                    <div style="font-size:12px;color:var(--gacov-text-muted)">Código: {{ $transfer->originWarehouse->code }}</div>
                    @endif
                </div>
                <div style="text-align:center;color:var(--gacov-primary)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-1)">Bodega destino</div>
                    <div style="font-weight:600;color:var(--gacov-text-primary)">
                        {{ $transfer->destinationWarehouse?->name ?? '—' }}
                    </div>
                    @if($transfer->destinationWarehouse)
                    <div style="font-size:12px;color:var(--gacov-text-muted)">Código: {{ $transfer->destinationWarehouse->code }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Auditoría --}}
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Auditoría</h2>
        </div>
        <div class="panel-body">
            <div style="display:grid;gap:var(--space-4)">
                <div>
                    <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-1)">Solicitado por</div>
                    <div style="font-weight:500">{{ $transfer->requestedBy?->name ?? '—' }}</div>
                    <div style="font-size:12px;color:var(--gacov-text-muted)">{{ $transfer->created_at->format('d/m/Y H:i') }}</div>
                </div>

                @if($transfer->approvedBy)
                <div>
                    <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-1)">Aprobado por</div>
                    <div style="font-weight:500">{{ $transfer->approvedBy->name }}</div>
                    <div style="font-size:12px;color:var(--gacov-text-muted)">{{ $transfer->approved_at?->format('d/m/Y H:i') }}</div>
                </div>
                @endif

                @if($transfer->completedBy)
                <div>
                    <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-1)">Completado por</div>
                    <div style="font-weight:500">{{ $transfer->completedBy->name }}</div>
                    <div style="font-size:12px;color:var(--gacov-text-muted)">{{ $transfer->completed_at?->format('d/m/Y H:i') }}</div>
                </div>
                @endif

                @if($transfer->notes)
                <div>
                    <div style="font-size:11px;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-1)">Notas</div>
                    <div style="font-size:13px;color:var(--gacov-text-secondary);line-height:1.5">{{ $transfer->notes }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tabla de ítems --}}
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-header">
        <h2 class="panel-title">Productos de la orden</h2>
    </div>
    <div class="panel-body" style="padding:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Producto</th>
                    <th style="text-align:center">Solicitado</th>
                    <th style="text-align:center">Despachado</th>
                    <th style="text-align:center">Recibido</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfer->items as $item)
                <tr>
                    <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $item->product->sku }}</code></td>
                    <td><strong>{{ $item->product->name }}</strong></td>
                    <td style="text-align:center;font-weight:600">{{ $item->quantity_requested }}</td>
                    <td style="text-align:center;color:var(--gacov-text-secondary)">
                        {{ $item->quantity_dispatched !== null ? $item->quantity_dispatched : '—' }}
                    </td>
                    <td style="text-align:center">
                        @if($item->quantity_received !== null)
                        @php
                            $diff = $item->quantity_received - $item->quantity_requested;
                        @endphp
                        <span style="font-weight:600;color:{{ $diff < 0 ? 'var(--gacov-error)' : ($diff > 0 ? 'var(--gacov-warning)' : 'var(--gacov-success)') }}">
                            {{ $item->quantity_received }}
                        </span>
                        @if($diff !== 0)
                        <span style="font-size:11px;color:{{ $diff < 0 ? 'var(--gacov-error)' : 'var(--gacov-warning)' }}">
                            ({{ $diff > 0 ? '+' : '' }}{{ $diff }})
                        </span>
                        @endif
                        @else
                        <span style="color:var(--gacov-text-muted)">—</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--gacov-text-muted)">
                        {{ $item->notes ?? '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--gacov-bg-elevated)">
                    <td colspan="2" style="padding:var(--space-3) var(--space-4);font-weight:600;font-size:13px">TOTALES</td>
                    <td style="text-align:center;font-weight:700;padding:var(--space-3) var(--space-4)">
                        {{ $transfer->items->sum('quantity_requested') }}
                    </td>
                    <td style="text-align:center;font-weight:700;padding:var(--space-3) var(--space-4)">
                        {{ $transfer->items->sum('quantity_dispatched') ?: '—' }}
                    </td>
                    <td style="text-align:center;font-weight:700;padding:var(--space-3) var(--space-4)">
                        {{ $transfer->items->whereNotNull('quantity_received')->sum('quantity_received') ?: '—' }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Formulario de completar traslado --}}
@if($transfer->status === 'aprobado')
@can('transfers.complete')
<div class="panel" id="completar">
    <div class="panel-header">
        <h2 class="panel-title">Completar traslado</h2>
    </div>
    <div class="panel-body" style="padding-top:0">
        <div class="alert" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:var(--gacov-info);margin-bottom:var(--space-5);margin-top:var(--space-5)">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            Registra las cantidades efectivamente recibidas en la bodega destino. Al completar se actualizará el stock en ambas bodegas.
        </div>

        <form method="POST" action="{{ route('transfers.complete', $transfer) }}" onsubmit="return confirm('¿Completar este traslado? Se actualizará el inventario en ambas bodegas.')">
            @csrf
            <table class="data-table" style="margin-bottom:var(--space-5)">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align:center">Solicitado</th>
                        <th style="text-align:center;width:180px">Cantidad recibida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->product->name }}</strong>
                            <br>
                            <code style="font-size:11px;color:var(--gacov-text-muted)">{{ $item->product->sku }}</code>
                        </td>
                        <td style="text-align:center;font-weight:600">{{ $item->quantity_requested }}</td>
                        <td style="text-align:center">
                            <input type="number"
                                   name="items[{{ $item->id }}][quantity_received]"
                                   value="{{ $item->quantity_requested }}"
                                   min="0"
                                   step="1"
                                   class="form-input"
                                   style="width:120px;text-align:center;margin:0 auto"
                                   required>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
                <a href="{{ route('transfers.index') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-secondary)">
                    Volver al listado
                </a>
                <button type="submit" class="btn btn-primary" style="width:auto;background:var(--gacov-secondary)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Confirmar recepción y completar traslado
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@endif

@if(in_array($transfer->status, ['completado', 'cancelado'], true))
<div style="text-align:center;margin-top:var(--space-4)">
    <a href="{{ route('transfers.index') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-secondary)">
        ← Volver al listado
    </a>
</div>
@endif
@endsection
