@extends('super-admin.layout')

@section('title', 'Nuevo Cliente')

@section('content')
<div class="page-header" style="margin-bottom:24px;">
    <a href="{{ route('super-admin.tenants.index') }}" style="color:var(--amr-text-muted); font-size:13px; display:inline-flex; align-items:center; gap:6px; margin-bottom:12px;">
        ← Volver a clientes
    </a>
    <h1 class="page-title">Nuevo Cliente</h1>
    <p class="page-subtitle">Registrar un nuevo tenant en el sistema SaaS</p>
</div>

<form method="POST" action="{{ route('super-admin.tenants.store') }}" style="max-width:680px;">
    @csrf

    {{-- Información básica --}}
    <div class="card" style="margin-bottom:20px; padding:24px;">
        <h3 style="font-size:15px; font-weight:600; margin-bottom:20px; color:var(--amr-primary);">Información de la empresa</h3>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div style="grid-column:1/-1;">
                <label class="form-label">Nombre de la empresa <span style="color:var(--amr-error)">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') is-error @enderror"
                    placeholder="Ej: Inversiones GACOV S.A.S." required>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="form-label">Slug (URL) <span style="color:var(--amr-error)">*</span></label>
                <input type="text" name="slug" id="slug" value="{{ old('slug') }}" class="form-input @error('slug') is-error @enderror"
                    placeholder="gacov" pattern="[a-z0-9-]+" required>
                <div style="font-size:11px; color:var(--amr-text-muted); margin-top:4px;">Solo letras minúsculas, números y guiones</div>
                @error('slug') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="form-label">NIT</label>
                <input type="text" name="nit" value="{{ old('nit') }}" class="form-input"
                    placeholder="900983146-1">
            </div>

            <div>
                <label class="form-label">Correo electrónico <span style="color:var(--amr-error)">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input @error('email') is-error @enderror"
                    placeholder="admin@empresa.com" required>
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="form-label">Teléfono</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-input"
                    placeholder="+57 300 000 0000">
            </div>
        </div>
    </div>

    {{-- Suscripción --}}
    <div class="card" style="margin-bottom:20px; padding:24px;">
        <h3 style="font-size:15px; font-weight:600; margin-bottom:20px; color:var(--amr-primary);">Plan de suscripción</h3>
        <p style="font-size:12px; color:var(--amr-text-muted); margin:-8px 0 18px;">
            El cliente queda creado con su plan comercial, pero la fase operativa inicia en F1.
            Luego puedes escalarlo desde su ficha en super admin según el avance real del proyecto.
        </p>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
            <div>
                <label class="form-label">Plan <span style="color:var(--amr-error)">*</span></label>
                <select name="plan_id" class="form-input @error('plan_id') is-error @enderror" required>
                    <option value="">— Seleccionar —</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                            {{ $plan->name }} — ${{ number_format($plan->monthly_price, 0, ',', '.') }}/mes
                        </option>
                    @endforeach
                </select>
                @error('plan_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="form-label">Ciclo de facturación <span style="color:var(--amr-error)">*</span></label>
                <select name="billing_cycle" class="form-input" required>
                    <option value="monthly" @selected(old('billing_cycle', 'monthly') === 'monthly')>Mensual</option>
                    <option value="yearly"  @selected(old('billing_cycle') === 'yearly')>Anual (-15%)</option>
                </select>
            </div>

            <div>
                <label class="form-label">Estado inicial <span style="color:var(--amr-error)">*</span></label>
                <select name="status" class="form-input" required>
                    <option value="trial"  @selected(old('status', 'trial') === 'trial')>Período de prueba (14 días)</option>
                    <option value="active" @selected(old('status') === 'active')>Activo</option>
                </select>
            </div>
        </div>
    </div>

    <div style="display:flex; gap:12px;">
        <button type="submit" class="btn-primary">Crear cliente</button>
        <a href="{{ route('super-admin.tenants.index') }}" class="btn-secondary">Cancelar</a>
    </div>
</form>

@push('scripts')
<script>
    // Auto-generate slug from name
    document.querySelector('[name=name]').addEventListener('input', function() {
        const slug = document.getElementById('slug');
        if (!slug.dataset.touched) {
            slug.value = this.value
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }
    });
    document.getElementById('slug').addEventListener('input', function() {
        this.dataset.touched = '1';
    });
</script>
@endpush
@endsection
