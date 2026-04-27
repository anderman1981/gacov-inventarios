@extends('layouts.app')
@section('title', 'Nuevo usuario')

@section('content')
<div class="page-header">
    <h1 class="page-title">Nuevo usuario</h1>
    <p class="page-subtitle">
        <a href="{{ route('admin.users.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">Usuarios</a> / Nuevo
    </p>
</div>

<div class="panel" style="max-width:860px">
    <div class="panel-header"><span class="panel-title">Datos del usuario</span></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5)">

                {{-- Nombre --}}
                <div class="form-group">
                    <label class="form-label" for="name">
                        Nombre completo <span style="color:var(--gacov-error)">*</span>
                    </label>
                    <input type="text" id="name" name="name"
                           class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name') }}" required maxlength="100">
                    @error('name')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Email --}}
                <div class="form-group">
                    <label class="form-label" for="email">
                        Correo electrónico <span style="color:var(--gacov-error)">*</span>
                    </label>
                    <input type="email" id="email" name="email"
                           class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           value="{{ old('email') }}" required>
                    @error('email')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Teléfono --}}
                <div class="form-group">
                    <label class="form-label" for="phone">Teléfono</label>
                    <input type="text" id="phone" name="phone"
                           class="form-input {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                           value="{{ old('phone') }}" maxlength="20" placeholder="Ej: 3001234567">
                    @error('phone')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Rol --}}
                <div class="form-group">
                    <label class="form-label" for="role">
                        Rol <span style="color:var(--gacov-error)">*</span>
                    </label>
                    <select id="role" name="role"
                            class="form-input {{ $errors->has('role') ? 'is-invalid' : '' }}"
                            required onchange="toggleRouteField(this.value)">
                        <option value="">Seleccionar rol...</option>
                        @foreach($roles as $key => $label)
                        <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $label)) }}
                        </option>
                        @endforeach
                    </select>
                    @error('role')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Contraseña --}}
                <div class="form-group">
                    <label class="form-label" for="password">
                        Contraseña <span style="color:var(--gacov-error)">*</span>
                    </label>
                    <input type="password" id="password" name="password"
                           class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           required minlength="8" autocomplete="new-password">
                    @error('password')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Confirmar contraseña --}}
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">
                        Confirmar contraseña <span style="color:var(--gacov-error)">*</span>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-input"
                           required minlength="8" autocomplete="new-password">
                </div>

                {{-- Ruta (solo conductor) --}}
                <div class="form-group" id="route-field"
                     style="display:{{ old('role') === 'conductor' ? 'block' : 'none' }}">
                    <label class="form-label" for="route_id">
                        Ruta asignada <span style="color:var(--gacov-error)">*</span>
                    </label>
                    <select id="route_id" name="route_id"
                            class="form-input {{ $errors->has('route_id') ? 'is-invalid' : '' }}">
                        <option value="">Seleccionar ruta...</option>
                        @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                            {{ $route->name }} @if($route->vehicle_plate)({{ $route->vehicle_plate }})@endif
                        </option>
                        @endforeach
                    </select>
                    @error('route_id')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Checkboxes --}}
                <div style="display:flex;flex-direction:column;gap:var(--space-4);justify-content:flex-end;padding-bottom:var(--space-2)">
                    <div style="display:flex;align-items:center;gap:var(--space-3)">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                               style="width:18px;height:18px;accent-color:var(--gacov-primary);cursor:pointer">
                        <label for="is_active" class="form-label" style="margin-bottom:0;cursor:pointer">
                            Usuario activo
                        </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:var(--space-3)">
                        <input type="hidden" name="must_change_password" value="0">
                        <input type="checkbox" id="must_change_password" name="must_change_password" value="1"
                               {{ old('must_change_password', '1') !== '0' ? 'checked' : '' }}
                               style="width:18px;height:18px;accent-color:var(--gacov-warning);cursor:pointer">
                        <label for="must_change_password" class="form-label" style="margin-bottom:0;cursor:pointer">
                            Debe cambiar contraseña al iniciar sesión
                        </label>
                    </div>
                </div>

            </div>

            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gacov-border)">
                <button type="submit" class="btn btn-primary" style="width:auto">Crear usuario</button>
                <a href="{{ route('admin.users.index') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleRouteField(role) {
    const routeField = document.getElementById('route-field');
    if (role === 'conductor') {
        routeField.style.display = 'block';
    } else {
        routeField.style.display = 'none';
        document.getElementById('route_id').value = '';
    }
}
// Inicializar al cargar
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');
    if (roleSelect) toggleRouteField(roleSelect.value);
});
</script>
@endpush
@endsection
