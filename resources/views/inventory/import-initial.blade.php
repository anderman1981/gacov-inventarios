@extends('layouts.app')

@section('title', 'Importar carga inicial')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
    <div>
        <h1 class="page-title">Importar carga inicial</h1>
        <p class="page-subtitle">
            Plantilla y carga masiva para la primera toma de inventario de la bodega principal, enfocada en cantidades por producto.
        </p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        <a href="{{ route('inventory.import.template') }}" class="btn btn-primary" style="width:auto">
            Descargar template
        </a>
        <a href="{{ route('inventory.warehouse') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
            Volver a bodega
        </a>
    </div>
</div>

@if(!$mainWarehouse)
<div class="alert alert-error">
    No existe una bodega principal activa para cargar inventario.
</div>
@else
<div style="display:grid;grid-template-columns:1.1fr .9fr;gap:var(--space-6);align-items:start">
    <section class="panel">
        <div class="panel-header">
            <span class="panel-title">Subir archivo</span>
            <span class="badge badge-info">{{ $mainWarehouse->name }}</span>
        </div>
        <div class="panel-body">
            <p style="margin-bottom:var(--space-4);color:var(--gacov-text-secondary)">
                Usa la plantilla oficial y llena una fila por producto. El sistema busca por <strong>código / SKU</strong> o por
                <strong>código WorldOffice</strong> y deja la cantidad exacta cargada en la bodega principal. En esta fase no se cargan
                precios ni valores.
            </p>

            <form method="POST" action="{{ route('inventory.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Archivo Excel o CSV</label>
                    <input type="file" name="inventory_file" class="form-input {{ $errors->has('inventory_file') ? 'is-invalid' : '' }}" accept=".xlsx,.xls,.csv,.txt" required>
                    @error('inventory_file')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="panel" style="margin:var(--space-5) 0 0;background:var(--gacov-bg-elevated)">
                    <div class="panel-body">
                        <div style="font-weight:600;margin-bottom:var(--space-2)">Formato esperado</div>
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

                <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6)">
                    <button type="submit" class="btn btn-primary" style="width:auto">Procesar importación</button>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <span class="panel-title">Recomendaciones</span>
        </div>
        <div class="panel-body" style="display:grid;gap:var(--space-4);color:var(--gacov-text-secondary)">
            <div>
                <div style="font-weight:600;color:var(--gacov-text-primary)">1. Descarga la plantilla</div>
                <div>Empieza siempre desde el archivo oficial para evitar columnas incorrectas.</div>
            </div>
            <div>
                <div style="font-weight:600;color:var(--gacov-text-primary)">2. Usa el código visible del producto</div>
                <div>El código debe coincidir con el SKU/código cargado en el catálogo de productos.</div>
            </div>
            <div>
                <div style="font-weight:600;color:var(--gacov-text-primary)">3. La cantidad es absoluta</div>
                <div>La importación deja el inventario final con la cantidad indicada en el archivo, no suma sobre la existente.</div>
            </div>
            <div>
                <div style="font-weight:600;color:var(--gacov-text-primary)">4. Solo controla cantidades</div>
                <div>En esta primera fase la operación se concentra en unidades por producto, no en precios, costos o valorización.</div>
            </div>
            <div>
                <div style="font-weight:600;color:var(--gacov-text-primary)">5. Revisa el historial</div>
                <div>Si una fila falla, el sistema la deja registrada y te muestra el conteo de errores.</div>
            </div>
        </div>
    </section>
</div>

<section class="panel" style="margin-top:var(--space-6)">
    <div class="panel-header">
        <span class="panel-title">Historial reciente</span>
    </div>
    @if($recentImports->isNotEmpty())
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
            @if(!empty($import->error_log))
            <tr>
                <td colspan="7" style="background:rgba(239,68,68,.05);color:var(--gacov-text-secondary)">
                    <strong>Detalle:</strong>
                    {{ implode(' | ', array_slice($import->error_log, 0, 5)) }}
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
    @else
    <div class="panel-body" style="color:var(--gacov-text-muted)">
        Aún no hay importaciones registradas para carga inicial.
    </div>
    @endif
</section>
@endif
@endsection
