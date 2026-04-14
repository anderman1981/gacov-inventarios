@extends('layouts.app')

@section('title', 'Carga inicial masiva de maquinas')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Excel operativo</span>
            <h1 class="inventory-hero__title">Carga inicial masiva de maquinas</h1>
            <p class="inventory-hero__subtitle">La manager puede inicializar bodegas de maquina por Excel una sola vez. Despues de esa carga, cualquier correccion vuelve al ajuste individual por maquina.</p>
            <div class="inventory-hero__badges">
                <span class="badge badge-info">Maquinas activas: {{ number_format($activeMachines, 0, ',', '.') }}</span>
                <span class="badge badge-success">Bodegas configuradas: {{ number_format($configuredWarehouses, 0, ',', '.') }}</span>
                <span class="badge {{ $machinesPendingInitialLoad > 0 ? 'badge-warning' : 'badge-neutral' }}">
                    Pendientes por inicializar: {{ number_format($machinesPendingInitialLoad, 0, ',', '.') }}
                </span>
            </div>
        </div>
        <div class="inventory-hero__actions">
            <a href="{{ route('inventory.machines.import.template') }}" class="btn btn-primary">Descargar template</a>
            <a href="{{ route('inventory.machines') }}" class="btn" style="background:#eaf1f7;color:#0f172a">Volver a maquinas</a>
        </div>
    </div>
</section>

<div class="inventory-card-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Subir archivo</div>
                <p class="inventory-location-card__subtitle">Una fila por producto y por maquina. Solo aplica para maquinas que aun no tengan carga inicial registrada.</p>
            </div>
            <span class="badge {{ $machinesPendingInitialLoad > 0 ? 'badge-warning' : 'badge-neutral' }}">
                {{ $machinesPendingInitialLoad > 0 ? 'Inicializacion habilitada' : 'Carga masiva cerrada' }}
            </span>
        </div>
        <div class="inventory-location-card__body">
            @if($machinesPendingInitialLoad > 0)
            <form method="POST" action="{{ route('inventory.machines.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Archivo de carga</label>
                    <input type="file" name="inventory_file" class="form-input {{ $errors->has('inventory_file') ? 'is-invalid' : '' }}" accept=".xlsx,.xls,.csv,.txt" required>
                    @error('inventory_file')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="inventory-panel-note" style="border:1px solid #dbe5ef;border-radius:18px;margin-top:var(--space-4);">
                    El template inicializa solo maquinas sin historial. Si una maquina ya tuvo carga inicial, el import la rechazara y deberas corregirla desde su tarjeta individual.
                </div>

                <div style="margin-top:var(--space-5)">
                    <button type="submit" class="btn btn-primary" style="width:auto">Procesar carga inicial</button>
                </div>
            </form>
            @else
            <div class="inventory-empty" style="padding:var(--space-5) 0">
                <p class="inventory-empty__title">La carga masiva ya no esta disponible</p>
                <p>Todas las maquinas configuradas ya tienen carga inicial. Desde este punto los cambios deben hacerse por maquina para conservar trazabilidad.</p>
            </div>
            @endif

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
                            <td><code>codigo_maquina</code></td>
                            <td>Si</td>
                            <td><code>M104</code></td>
                        </tr>
                        <tr>
                            <td><code>codigo_producto</code></td>
                            <td>Si</td>
                            <td><code>124</code></td>
                        </tr>
                        <tr>
                            <td><code>cantidad_inicial</code></td>
                            <td>Si</td>
                            <td><code>18</code></td>
                        </tr>
                        <tr>
                            <td><code>observaciones</code></td>
                            <td>No</td>
                            <td><code>Carga inicial maquina sede norte</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Regla operativa</div>
                <p class="inventory-location-card__subtitle">El lote inicial existe solo para arrancar rapido y luego se cierra.</p>
            </div>
        </div>
        <div class="inventory-location-card__body">
            <div class="inventory-meta-strip">
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Carga inicial</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Masiva por Excel</div>
                    <div class="inventory-table-product__meta">Pensada para poblar varias maquinas al inicio.</div>
                </div>
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Correcciones</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Una por una</div>
                    <div class="inventory-table-product__meta">Cada ajuste posterior se hace desde la tarjeta de la maquina.</div>
                </div>
                <div class="inventory-meta-card">
                    <div class="inventory-meta-card__label">Control admin</div>
                    <div class="inventory-meta-card__value" style="font-size:18px">Trazabilidad total</div>
                    <div class="inventory-table-product__meta">Se evita reabrir la carga masiva cuando ya hay historial en bodega.</div>
                </div>
            </div>
        </div>
    </section>
</div>

<section class="panel inventory-table-panel">
    <div class="inventory-results-bar">
        <span>Historial reciente: <strong>{{ number_format($recentImports->count(), 0, ',', '.') }}</strong> cargas iniciales</span>
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
        <p class="inventory-empty__title">Sin historial todavia</p>
        <p>Aun no hay cargas iniciales masivas registradas para maquinas.</p>
    </div>
    @endif
</section>
</div>
@endsection
