@extends('super-admin.layout')

@section('title', 'Editar — ' . $tenant->name)

@section('content')
<div class="page-header" style="margin-bottom:24px;">
    <a href="{{ route('super-admin.tenants.show', $tenant) }}" style="color:var(--amr-text-muted); font-size:13px; display:inline-flex; align-items:center; gap:6px; margin-bottom:12px;">
        ← Volver al cliente
    </a>
    <h1 class="page-title">Editar cliente</h1>
    <p class="page-subtitle">{{ $tenant->name }}</p>
</div>

<form method="POST" action="{{ route('super-admin.tenants.update', $tenant) }}" style="max-width:680px;">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div style="background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.3); border-radius:10px; padding:14px 16px; margin-bottom:20px;">
            <ul style="list-style:none; color:var(--amr-error); font-size:13px;">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-bottom:20px; padding:24px;">
        <h3 style="font-size:15px; font-weight:600; margin-bottom:20px; color:var(--amr-primary);">Información de la empresa</h3>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div style="grid-column:1/-1;">
                <label class="form-label">Nombre de la empresa <span style="color:var(--amr-error)">*</span></label>
                <input type="text" name="name" value="{{ old('name', $tenant->name) }}" class="form-input @error('name') is-error @enderror" required>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="form-label">Slug (URL) <span style="color:var(--amr-error)">*</span></label>
                <input type="text" name="slug" value="{{ old('slug', $tenant->slug) }}" class="form-input @error('slug') is-error @enderror"
                    pattern="[a-z0-9-]+" required>
                <div style="font-size:11px; color:var(--amr-text-muted); margin-top:4px;">Solo letras minúsculas, números y guiones</div>
                @error('slug') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="form-label">NIT</label>
                <input type="text" name="nit" value="{{ old('nit', $tenant->nit) }}" class="form-input" placeholder="900983146-1">
            </div>

            <div>
                <label class="form-label">Correo electrónico <span style="color:var(--amr-error)">*</span></label>
                <input type="email" name="email" value="{{ old('email', $tenant->email) }}" class="form-input @error('email') is-error @enderror" required>
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="form-label">Teléfono</label>
                <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" class="form-input" placeholder="+57 300 000 0000">
            </div>

            <div>
                <label class="form-label">Estado</label>
                <select name="is_active" class="form-input">
                    <option value="1" @selected(old('is_active', $tenant->is_active))>Activo</option>
                    <option value="0" @selected(!old('is_active', $tenant->is_active))>Inactivo</option>
                </select>
            </div>
        </div>
    </div>

    <div style="display:flex; gap:12px;">
        <button type="submit" class="btn-primary">Guardar cambios</button>
        <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
