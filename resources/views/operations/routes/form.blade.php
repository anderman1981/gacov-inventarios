@extends('layouts.app')

@section('title', $route ? 'Editar ruta' : 'Nueva ruta')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
    <div>
        <h1 class="page-title">{{ $route ? 'Editar ruta' : 'Nueva ruta' }}</h1>
        <p class="page-subtitle">Define el ID, el nombre y el conductor asignado para esta ruta.</p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        <a href="{{ route('operations.routes.board') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">Volver al tablero</a>
    </div>
</div>

<div class="panel">
    <div class="panel-body" style="padding:var(--space-6)">
        <form method="POST" action="{{ $route ? route('operations.routes.update', $route) : route('operations.routes.store') }}" style="display:grid;gap:var(--space-5)">
            @csrf
            @if($route)
            @method('PUT')
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-4)">
                <div class="form-group">
                    <label class="form-label" for="route-code">ID de ruta</label>
                    <input id="route-code" name="code" type="text" class="form-input @error('code') is-invalid @enderror" value="{{ old('code', $route?->code) }}" placeholder="RT-01" required>
                    @error('code')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="route-name">Nombre de la ruta</label>
                    <input id="route-name" name="name" type="text" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $route?->name) }}" placeholder="Ruta Norte" required>
                    @error('name')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-4)">
                <div class="form-group">
                    <label class="form-label" for="route-plate">Placa del vehículo</label>
                    <input id="route-plate" name="vehicle_plate" type="text" class="form-input @error('vehicle_plate') is-invalid @enderror" value="{{ old('vehicle_plate', $route?->vehicle_plate) }}" placeholder="ABC123">
                    @error('vehicle_plate')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="route-driver">Conductor</label>
                    <select id="route-driver" name="driver_user_id" class="form-input @error('driver_user_id') is-invalid @enderror">
                        <option value="">Sin conductor asignado</option>
                        @foreach($conductors as $conductor)
                        <option value="{{ $conductor->id }}" {{ (string) old('driver_user_id', $route?->driver_user_id) === (string) $conductor->id ? 'selected' : '' }}>
                            {{ $conductor->name }} — {{ $conductor->email }}
                        </option>
                        @endforeach
                    </select>
                    @error('driver_user_id')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:10px;font-size:14px;color:var(--gacov-text-secondary)">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $route?->is_active ?? true) ? 'checked' : '' }}>
                    Ruta activa
                </label>
            </div>

            <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
                <button type="submit" class="btn btn-primary" style="width:auto">{{ $route ? 'Guardar cambios' : 'Crear ruta' }}</button>
                @if($route)
                <a href="{{ route('operations.routes.board') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">Cancelar</a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
