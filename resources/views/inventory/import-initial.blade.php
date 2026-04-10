@extends('layouts.app')

@section('title', 'Importar carga inicial')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Carga masiva</span>
            <h1 class="inventory-hero__title">Importar carga inicial</h1>
            <p class="inventory-hero__subtitle">Plantilla y carga masiva para la primera toma de inventario de la bodega principal, enfocada solo en cantidades por producto.</p>
            @if($mainWarehouse)
            <div class="inventory-hero__badges">
                <span class="badge badge-info">{{ $mainWarehouse->name }}</span>
                <span class="badge badge-neutral">Bodega principal</span>
            </div>
            @endif
        </div>
        <div class="inventory-hero__actions">
            <a href="{{ route('inventory.import.template') }}" class="btn btn-primary">Descargar template</a>
            <a href="{{ route('inventory.warehouse') }}" class="btn" style="background:#eaf1f7;color:#0f172a">Volver a bodega</a>
        </div>
    </div>
</section>

@if(!$mainWarehouse)
<div class="alert alert-error">
    No existe una bodega principal activa para cargar inventario.
</div>
@else
<div class="inventory-card-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Subir archivo</div>
                <p class="inventory-location-card__subtitle">Archivo oficial para la primera carga del inventario.</p>
            </div>
            <span class="badge badge-info">{{ $mainWarehouse->name }}</span>
        </div>
        <div class="inventory-location-card__body">
            <form method="POST" action="{{ route('inventory.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Archivo Excel o CSV</label>
                    <input type="file" name="inventory_file" class="form-input {{ $errors->has('inventory_file') ? 'is-invalid' : '' }}" accept=".xlsx,.xls,.csv,.txt" required>
                    @error('inventory_file')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="inventory-panel-note" style="border:1px solid #dbe5ef;border-radius:18px;margin-top:var(--space-4);">
                    Usa el archivo oficial y llena una fila por producto. El sistema toma <strong>codigo del producto</strong> y <strong>cantidad inicial</strong>. No se importan precios ni valores.
                </div>

                <div style="margin-top:var(--space-5)">
                    <button type="submit" class="btn btn-primary" style="width:auto">Procesar importación</button>
                </div>
            </form>

            <div class="table-scroll" style="margin-top:var(--space-5)">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Columna</th>
                            <th>Obligatoria</th>
                            <th>Ejemplo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>codigo_producto</code></td>
                            <td>Sí</td>
                            <td><code>124</code></td>
                        </tr>
                        <tr>
                            <td><code>cantidad_inicial</code></td>
                            <td>Sí</td>
                            <td><code>36</code></td>
                        </tr>
                        <tr>
                            <td><code>observaciones</code></td>
                            <td>No</td>
                            <td><code>Carga inicial abril</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Guía rápida</div>
                <p class="inventory-location-card__subtitle">Lo mínimo para evitar errores en la primera carga.</p>
            </div>
        </div>
        <div class="inventory-location-card__body">
            <div class="inventory-meta-strip">
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Paso 1</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Descargar</div>
                    <div class="inventory-table-product__meta">Empieza siempre con la plantilla oficial.</div>
                </div>
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Paso 2</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Llenar</div>
                    <div class="inventory-table-product__meta">Usa el codigo visible del producto.</div>
                </div>
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Paso 3</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Cargar</div>
                    <div class="inventory-table-product__meta">La cantidad se toma como valor final.</div>
                </div>
            </div>
        </div>
    </section>
</div>

<section class="panel inventory-table-panel">
    <div class="inventory-results-bar">
        <span>Historial reciente: <strong>{{ number_format($recentImports->count(), 0, ',', '.') }}</strong> importaciones</span>
    </div>
    @if($recentImports->isNotEmpty())
    <div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Archivo</th>
                <th>Estado</th>
                <th style="text-align:center">Filas</th>
                <th style="text-align:center">Procesadas</th>
                <th style="text-align:center">Errores</th>
                <th>Importado por</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentImports as $import)
            <tr>
                <td>{{ $import->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $import->file_name }}</td>
                <td>
                    <span class="badge {{ $import->status === 'completado' ? 'badge-success' : ($import->status === 'error' ? 'badge-error' : 'badge-warning') }}">
                        {{ ucfirst($import->status) }}
                    </span>
                </td>
                <td style="text-align:center">{{ $import->total_rows }}</td>
                <td style="text-align:center">{{ $import->processed_rows }}</td>
                <td style="text-align:center">{{ $import->error_rows }}</td>
                <td>{{ $import->user?->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <div class="inventory-empty">
        <p class="inventory-empty__title">Sin historial todavía</p>
        <p>Aún no hay importaciones registradas para carga inicial.</p>
    </div>
    @endif
</section>
@endif
</div>
@endsection
