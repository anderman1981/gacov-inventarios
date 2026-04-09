@extends('layouts.app')
@section('title', 'Nueva máquina')

@section('content')
<div class="page-header">
    <h1 class="page-title">Nueva máquina</h1>
    <p class="page-subtitle">
        <a href="{{ route('machines.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">Máquinas</a> / Nueva
    </p>
</div>

<div class="panel" style="max-width:860px">
    <div class="panel-header"><span class="panel-title">Datos de la máquina</span></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('machines.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5)">

                {{-- Código --}}
                <div class="form-group">
                    <label class="form-label">Código <span style="color:var(--gacov-error)">*</span></label>
                    <input type="text" name="code"
                           class="form-input {{ $errors->has('code') ? 'is-invalid' : '' }}"
                           value="{{ old('code') }}"
                           placeholder="Ej: MAQ-001"
                           maxlength="20"
                           required>
                    @error('code')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Código WorldOffice --}}
                <div class="form-group">
                    <label class="form-label">Código WorldOffice</label>
                    <input type="text" name="worldoffice_code"
                           class="form-input {{ $errors->has('worldoffice_code') ? 'is-invalid' : '' }}"
                           value="{{ old('worldoffice_code') }}"
                           placeholder="Ej: WO-001"
                           maxlength="20">
                    @error('worldoffice_code')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Nombre --}}
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Nombre <span style="color:var(--gacov-error)">*</span></label>
                    <input type="text" name="name"
                           class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name') }}"
                           placeholder="Nombre descriptivo de la máquina"
                           maxlength="150"
                           required>
                    @error('name')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Ubicación --}}
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Ubicación</label>
                    <input type="text" name="location"
                           class="form-input {{ $errors->has('location') ? 'is-invalid' : '' }}"
                           value="{{ old('location') }}"
                           placeholder="Dirección o descripción de la ubicación"
                           maxlength="255">
                    @error('location')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Ruta --}}
                <div class="form-group">
                    <label class="form-label">Ruta</label>
                    <select name="route_id" class="form-input {{ $errors->has('route_id') ? 'is-invalid' : '' }}">
                        <option value="">Sin ruta asignada</option>
                        @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                            {{ $route->name }}
                            @if($route->code) ({{ $route->code }}) @endif
                        </option>
                        @endforeach
                    </select>
                    @error('route_id')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Operador --}}
                <div class="form-group">
                    <label class="form-label">Operador</label>
                    <select name="operator_user_id" class="form-input {{ $errors->has('operator_user_id') ? 'is-invalid' : '' }}">
                        <option value="">Sin operador asignado</option>
                        @foreach($operators as $operator)
                        <option value="{{ $operator->id }}" {{ old('operator_user_id') == $operator->id ? 'selected' : '' }}>
                            {{ $operator->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('operator_user_id')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Tipo --}}
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <input type="text" name="type"
                           class="form-input {{ $errors->has('type') ? 'is-invalid' : '' }}"
                           value="{{ old('type') }}"
                           placeholder="Ej: Dispensadora, Café, Snacks..."
                           maxlength="50">
                    @error('type')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Activo --}}
                <div class="form-group" style="display:flex;align-items:center;gap:var(--space-3);padding-top:var(--space-6)">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                           style="width:18px;height:18px;accent-color:var(--gacov-primary)">
                    <label for="is_active" class="form-label" style="margin-bottom:0;cursor:pointer">Máquina activa</label>
                </div>

            </div>

            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gacov-border)">
                <button type="submit" class="btn btn-primary" style="width:auto">Guardar máquina</button>
                <a href="{{ route('machines.index') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
