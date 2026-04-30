@extends('layouts.app')

@section('title', 'Tabla temporal de compra')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Tabla temporal</span>
            <h1 class="inventory-hero__title">Compra #{{ $batch->id }}</h1>
            <p class="inventory-hero__subtitle">{{ $batch->original_file_name }}. Revisa las filas antes de cargar esta compra a {{ $batch->warehouse?->name ?? 'bodega' }}.</p>
            <div class="inventory-hero__badges">
                <span class="badge {{ $batch->status === 'procesado' ? 'badge-success' : ($batch->status === 'descartado' ? 'badge-neutral' : 'badge-warning') }}">{{ ucfirst($batch->status) }}</span>
                <span class="badge badge-info">{{ number_format($batch->valid_rows, 0, ',', '.') }} válidas</span>
                <span class="badge {{ $batch->error_rows > 0 ? 'badge-error' : 'badge-success' }}">{{ number_format($batch->error_rows, 0, ',', '.') }} errores</span>
            </div>
        </div>
        <div class="inventory-hero__actions">
            <a href="{{ route('inventory.purchases.index') }}" class="btn" style="background:#eaf1f7;color:#0f172a">Volver a compras</a>
            @if($batch->status === 'borrador')
                <form method="POST" action="{{ route('inventory.purchases.destroy', $batch) }}" onsubmit="return confirm('¿Descartar esta compra temporal? El inventario no se modificará.')" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn" style="background:#fee2e2;color:#991b1b;width:auto">Descartar</button>
                </form>
            @endif
        </div>
    </div>
</section>

<div class="kpi-grid" style="margin-bottom:var(--space-8)">
    <div class="kpi-card" style="--kpi-accent:#00D4FF;--kpi-bg:rgba(0,212,255,.1)">
        <div class="kpi-value">{{ number_format($batch->total_rows, 0, ',', '.') }}</div>
        <div class="kpi-label">Filas CSV</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#10B981;--kpi-bg:rgba(16,185,129,.1)">
        <div class="kpi-value">{{ number_format($batch->total_units, 0, ',', '.') }}</div>
        <div class="kpi-label">Unidades por cargar</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#7C3AED;--kpi-bg:rgba(124,58,237,.1)">
        <div class="kpi-value">${{ number_format((float) $batch->total_cost, 0, ',', '.') }}</div>
        <div class="kpi-label">Costo estimado</div>
    </div>
</div>

<section class="panel inventory-table-panel">
    <div class="inventory-results-bar">
        <span>Esta tabla todavía no modifica inventario.</span>
        @if($batch->canBeConfirmed())
        <form method="POST" action="{{ route('inventory.purchases.confirm', $batch) }}" onsubmit="return confirm('¿Confirmar y cargar esta compra a bodega?')" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-primary" style="width:auto">Confirmar y cargar compra</button>
        </form>
        @elseif($batch->status === 'borrador')
        <span class="badge badge-error">Corrige los errores y vuelve a subir el CSV</span>
        @else
        <span class="badge badge-neutral">Sin acciones pendientes</span>
        @endif
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align:center">Fila</th>
                    <th>Producto</th>
                    <th style="text-align:center">Cantidad</th>
                    <th>Costo unitario</th>
                    <th>Proveedor</th>
                    <th>Factura</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td style="text-align:center">{{ $row->row_number }}</td>
                    <td>
                        <div class="inventory-table-product">
                            <span class="inventory-table-product__name">{{ $row->product?->name ?? $row->product_name ?? 'Producto no encontrado' }}</span>
                            <span class="inventory-table-product__meta"><code>{{ $row->product_code }}</code></span>
                            @if($row->error_message)
                            <span class="inventory-table-product__meta" style="color:#b91c1c">{{ $row->error_message }}</span>
                            @endif
                        </div>
                    </td>
                    <td style="text-align:center">{{ number_format($row->quantity, 0, ',', '.') }}</td>
                    <td>${{ number_format((float) $row->unit_cost, 0, ',', '.') }}</td>
                    <td>{{ $row->supplier ?? '—' }}</td>
                    <td>{{ $row->invoice_number ?? '—' }}</td>
                    <td>{{ $row->purchase_date?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $row->status === 'valida' ? 'badge-success' : 'badge-error' }}">
                            {{ $row->status === 'valida' ? 'Válida' : 'Error' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:var(--space-4)">{{ $rows->links() }}</div>
</section>
</div>
@endsection
