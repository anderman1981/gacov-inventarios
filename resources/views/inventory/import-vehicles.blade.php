@extends('layouts.app')

@section('title', 'Carga masiva de vehículos')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Excel operativo</span>
            <h1 class="inventory-hero__title">Carga masiva de vehículos</h1>
            <p class="inventory-hero__subtitle">La manager puede cargar o ajustar inventario de vehículos desde template Excel. Este flujo no usa imágenes: trabaja por ruta, producto y cantidad final.</p>
            <div class="inventory-hero__badges">
                <span class="badge badge-info">Rutas activas: {{ number_format($activeRoutes, 0, ',', '.') }}</span>
                <span class="badge badge-success">Vehículos configurados: {{ number_format($configuredVehicles, 0, ',', '.') }}</span>
                <span class="badge badge-neutral">Formato oficial AMR</span>
            </div>
        </div>
        <div class="inventory-hero__actions">
            <a href="{{ route('inventory.vehicles.import.template') }}" class="btn btn-primary">Descargar template</a>
            <a href="{{ route('inventory.vehicles') }}" class="btn" style="background:#eaf1f7;color:#0f172a">Volver a vehículos</a>
        </div>
    </div>
</section>

<div class="inventory-card-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Subir archivo</div>
                <p class="inventory-location-card__subtitle">Una fila por producto y por ruta. La cantidad importada se toma como valor final dentro del vehículo.</p>
            </div>
            <span class="badge badge-warning">Solo Excel o CSV</span>
        </div>
        <div class="inventory-location-card__body">
            <form method="POST" action="{{ route('inventory.vehicles.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Archivo de carga</label>
                    <input type="file" name="inventory_file" class="form-input {{ $errors->has('inventory_file') ? 'is-invalid' : '' }}" accept=".xlsx,.xls,.csv,.txt" required>
                    @error('inventory_file')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="inventory-panel-note" style="border:1px solid #dbe5ef;border-radius:18px;margin-top:var(--space-4);">
                    Usa el template descargado. Si el vehículo ya tuvo carga inicial, la columna <strong>observaciones</strong> debe explicar el motivo del ajuste para notificar al admin.
                </div>

                <div style="margin-top:var(--space-5)">
                    <button type="submit" class="btn btn-primary" style="width:auto">Procesar carga masiva</button>
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
                            <td><code>codigo_ruta</code></td>
                            <td>Sí</td>
                            <td><code>RT1</code></td>
                        </tr>
                        <tr>
                            <td><code>codigo_producto</code></td>
                            <td>Sí</td>
                            <td><code>124</code></td>
                        </tr>
                        <tr>
                            <td><code>cantidad_total</code></td>
                            <td>Sí</td>
                            <td><code>36</code></td>
                        </tr>
                        <tr>
                            <td><code>observaciones</code></td>
                            <td>Condicional</td>
                            <td><code>Ajuste por conteo de cierre</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Cómo funciona</div>
                <p class="inventory-location-card__subtitle">Regla operativa para proteger trazabilidad de la manager.</p>
            </div>
        </div>
        <div class="inventory-location-card__body">
            <div class="inventory-meta-strip">
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Carga inicial</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Sin observación</div>
                    <div class="inventory-table-product__meta">Aplica cuando el vehículo todavía no tiene historial.</div>
                </div>
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Ajuste posterior</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Con observación</div>
                    <div class="inventory-table-product__meta">Si ya hubo carga inicial, la observación es obligatoria.</div>
                </div>
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Control admin</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Automático</div>
                    <div class="inventory-table-product__meta">Cada ajuste posterior notifica al admin para revisión.</div>
                </div>
            </div>
        </div>
    </section>
</div>

<section class="panel inventory-table-panel">
    <div class="inventory-results-bar">
        <span>Historial reciente: <strong>{{ number_format($recentImports->count(), 0, ',', '.') }}</strong> cargas masivas</span>
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
        <p>Aún no hay cargas masivas registradas para vehículos.</p>
    </div>
    @endif
</section>
</div>
@endsection
