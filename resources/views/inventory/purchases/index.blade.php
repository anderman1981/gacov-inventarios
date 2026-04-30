@extends('layouts.app')

@section('title', 'Compras CSV')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Compra en revisión</span>
            <h1 class="inventory-hero__title">Compras CSV</h1>
            <p class="inventory-hero__subtitle">Carga compras a una tabla temporal, revisa producto por producto y confirma solo cuando la información esté lista para entrar a bodega.</p>
            @if($mainWarehouse)
            <div class="inventory-hero__badges">
                <span class="badge badge-info">{{ $mainWarehouse->name }}</span>
                <span class="badge badge-neutral">No impacta stock al subir</span>
            </div>
            @endif
        </div>
        <div class="inventory-hero__actions">
            <a href="{{ route('inventory.purchases.template') }}" class="btn btn-primary">Descargar CSV</a>
            <a href="{{ route('inventory.warehouse') }}" class="btn" style="background:#eaf1f7;color:#0f172a">Volver a bodega</a>
        </div>
    </div>
</section>

@if(!$mainWarehouse)
<div class="alert alert-error">No existe una bodega principal activa para recibir compras.</div>
@else
<div class="inventory-card-grid" style="grid-template-columns:minmax(320px,1fr) minmax(320px,.8fr);">
    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Subir compra en CSV</div>
                <p class="inventory-location-card__subtitle">El archivo queda en estado borrador hasta que confirmes la carga.</p>
            </div>
            <span class="badge badge-warning">Temporal</span>
        </div>
        <div class="inventory-location-card__body">
            <form method="POST" action="{{ route('inventory.purchases.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Archivo CSV</label>
                    <input type="file" name="purchase_file" class="form-input {{ $errors->has('purchase_file') ? 'is-invalid' : '' }}" accept=".csv,.txt" required>
                    @error('purchase_file')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--space-4)">
                    <div class="form-group">
                        <label class="form-label">Proveedor por defecto</label>
                        <input type="text" name="supplier" class="form-input" value="{{ old('supplier') }}" maxlength="150">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Factura por defecto</label>
                        <input type="text" name="invoice_number" class="form-input" value="{{ old('invoice_number') }}" maxlength="80">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha por defecto</label>
                        <input type="date" name="purchase_date" class="form-input" value="{{ old('purchase_date') }}">
                    </div>
                </div>

                <div class="inventory-panel-note" style="border:1px solid #dbe5ef;border-radius:18px;margin-top:var(--space-4);">
                    Subir el CSV solo crea una vista previa. La bodega se actualiza únicamente con <strong>Confirmar y cargar compra</strong>.
                </div>

                <div style="margin-top:var(--space-5)">
                    <button type="submit" class="btn btn-primary" style="width:auto">Subir y revisar tabla</button>
                </div>
            </form>
        </div>
    </section>

    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Columnas del CSV</div>
                <p class="inventory-location-card__subtitle">Usa punto o coma para decimales. Se recomienda separador punto y coma.</p>
            </div>
        </div>
        <div class="inventory-location-card__body">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Columna</th>
                            <th>Obligatoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>codigo_producto</code></td><td>Sí</td></tr>
                        <tr><td><code>cantidad</code></td><td>Sí</td></tr>
                        <tr><td><code>costo_unitario</code></td><td>No</td></tr>
                        <tr><td><code>proveedor</code></td><td>No</td></tr>
                        <tr><td><code>factura</code></td><td>No</td></tr>
                        <tr><td><code>fecha_compra</code></td><td>No</td></tr>
                        <tr><td><code>observaciones</code></td><td>No</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<section class="panel inventory-table-panel">
    <div class="inventory-results-bar">
        <span>Compras temporales: <strong>{{ number_format($batches->total(), 0, ',', '.') }}</strong></span>
    </div>
    @if($batches->count() > 0)
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Archivo</th>
                    <th>Estado</th>
                    <th style="text-align:center">Filas</th>
                    <th style="text-align:center">Errores</th>
                    <th style="text-align:center">Unidades</th>
                    <th>Total costo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches as $batch)
                <tr>
                    <td>{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="inventory-table-product">
                            <span class="inventory-table-product__name">{{ $batch->original_file_name }}</span>
                            <span class="inventory-table-product__meta">{{ $batch->supplier ?? 'Sin proveedor' }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $batch->status === 'procesado' ? 'badge-success' : ($batch->status === 'descartado' ? 'badge-neutral' : 'badge-warning') }}">
                            {{ ucfirst($batch->status) }}
                        </span>
                    </td>
                    <td style="text-align:center">{{ number_format($batch->total_rows, 0, ',', '.') }}</td>
                    <td style="text-align:center">{{ number_format($batch->error_rows, 0, ',', '.') }}</td>
                    <td style="text-align:center">{{ number_format($batch->total_units, 0, ',', '.') }}</td>
                    <td>${{ number_format((float) $batch->total_cost, 0, ',', '.') }}</td>
                    <td><a href="{{ route('inventory.purchases.show', $batch) }}" class="inventory-action-link">Ver tabla</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:var(--space-4)">{{ $batches->links() }}</div>
    @else
    <div class="inventory-empty">
        <p class="inventory-empty__title">Sin compras temporales</p>
        <p>Sube el primer CSV para revisar la compra antes de cargarla a inventario.</p>
    </div>
    @endif
</section>
@endif
</div>
@endsection
