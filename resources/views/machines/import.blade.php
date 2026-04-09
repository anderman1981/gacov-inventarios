@extends('layouts.app')
@section('title', 'Carga masiva de máquinas y rutas')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap;margin-bottom:var(--space-6)">
    <div>
        <div style="margin-bottom:var(--space-2)">
            <a href="{{ route('machines.index') }}" style="color:var(--gacov-text-muted);text-decoration:none;font-size:13px">← Máquinas</a>
        </div>
        <h1 class="page-title">Carga masiva de máquinas y rutas</h1>
        <p class="page-subtitle">Primero carga las rutas y luego las máquinas para dejarlas relacionadas desde el archivo.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:var(--space-5)">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="alert alert-error" style="margin-bottom:var(--space-5)">{{ session('error') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);align-items:start">
    <div class="panel">
        <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-3);flex-wrap:wrap">
            <h2 class="panel-title">Rutas</h2>
            <a href="{{ route('machines.import.template.routes') }}" class="btn btn-primary" style="width:auto">Descargar template</a>
        </div>
        <div class="panel-body">
            <p style="color:var(--gacov-text-secondary);margin-bottom:var(--space-4)">
                Archivo base para crear o actualizar rutas y su vehículo asociado. Columnas esperadas:
                <code>codigo_ruta</code>, <code>nombre_ruta</code>, <code>placa_vehiculo</code>,
                <code>email_conductor</code> y <code>activa</code>.
            </p>

            <form method="POST" action="{{ route('machines.import.store.routes') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="routes-import-file">Archivo de rutas</label>
                    <input id="routes-import-file" type="file" name="import_file" class="form-input" accept=".xlsx,.xls,.csv,.txt" required>
                    @error('import_file')
                    <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="width:auto">Importar rutas</button>
            </form>

            <div style="margin-top:var(--space-5);padding:var(--space-4);background:rgba(0,212,255,.06);border:1px solid rgba(0,212,255,.14);border-radius:var(--radius-lg)">
                <div style="font-weight:600;margin-bottom:var(--space-2)">Notas rápidas</div>
                <div style="font-size:13px;color:var(--gacov-text-secondary)">Si el email del conductor viene vacío, la ruta queda sin conductor asignado.</div>
                <div style="font-size:13px;color:var(--gacov-text-secondary)">La importación crea o actualiza la bodega de vehículo ligada a cada ruta.</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-3);flex-wrap:wrap">
            <h2 class="panel-title">Máquinas</h2>
            <a href="{{ route('machines.import.template.machines') }}" class="btn btn-primary" style="width:auto">Descargar template</a>
        </div>
        <div class="panel-body">
            <p style="color:var(--gacov-text-secondary);margin-bottom:var(--space-4)">
                Archivo base para crear o actualizar máquinas. Columnas esperadas:
                <code>codigo_maquina</code>, <code>codigo_worldoffice</code>, <code>nombre_maquina</code>,
                <code>ubicacion</code>, <code>codigo_ruta</code>, <code>email_operador</code>,
                <code>tipo</code> y <code>activa</code>.
            </p>

            <form method="POST" action="{{ route('machines.import.store.machines') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="machines-import-file">Archivo de máquinas</label>
                    <input id="machines-import-file" type="file" name="import_file" class="form-input" accept=".xlsx,.xls,.csv,.txt" required>
                    @error('import_file')
                    <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="width:auto">Importar máquinas</button>
            </form>

            <div style="margin-top:var(--space-5);padding:var(--space-4);background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.14);border-radius:var(--radius-lg)">
                <div style="font-weight:600;margin-bottom:var(--space-2)">Notas rápidas</div>
                <div style="font-size:13px;color:var(--gacov-text-secondary)">La ruta debe existir antes de cargar la máquina si vas a enviarla en <code>codigo_ruta</code>.</div>
                <div style="font-size:13px;color:var(--gacov-text-secondary)">La importación crea o actualiza automáticamente la bodega tipo máquina.</div>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-top:var(--space-6);align-items:start">
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Últimas importaciones de rutas</h2>
        </div>
        @if($recentRouteImports->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Archivo</th>
                    <th>Estado</th>
                    <th style="text-align:center">Procesadas</th>
                    <th style="text-align:center">Errores</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentRouteImports as $import)
                <tr>
                    <td>{{ $import->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $import->file_name }}</td>
                    <td><span class="badge {{ $import->status === 'completado' ? 'badge-success' : ($import->status === 'error' ? 'badge-error' : 'badge-warning') }}">{{ ucfirst($import->status) }}</span></td>
                    <td style="text-align:center">{{ $import->processed_rows }}</td>
                    <td style="text-align:center">{{ $import->error_rows }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="panel-body" style="color:var(--gacov-text-muted)">Aún no hay importaciones de rutas registradas.</div>
        @endif
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Últimas importaciones de máquinas</h2>
        </div>
        @if($recentMachineImports->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Archivo</th>
                    <th>Estado</th>
                    <th style="text-align:center">Procesadas</th>
                    <th style="text-align:center">Errores</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentMachineImports as $import)
                <tr>
                    <td>{{ $import->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $import->file_name }}</td>
                    <td><span class="badge {{ $import->status === 'completado' ? 'badge-success' : ($import->status === 'error' ? 'badge-error' : 'badge-warning') }}">{{ ucfirst($import->status) }}</span></td>
                    <td style="text-align:center">{{ $import->processed_rows }}</td>
                    <td style="text-align:center">{{ $import->error_rows }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="panel-body" style="color:var(--gacov-text-muted)">Aún no hay importaciones de máquinas registradas.</div>
        @endif
    </div>
</div>
@endsection
