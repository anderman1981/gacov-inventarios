@extends('layouts.app')

@section('title', 'Nuevo contrato')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('contracts.index') }}" class="back-link">← Contratos</a>
        <h1>Nuevo contrato interactivo</h1>
        <p class="page-subtitle">Completa los datos una vez y el sistema enviará el enlace de firma al cliente.</p>
    </div>
</div>

<div class="contract-create-layout">
    <form method="POST" action="{{ route('contracts.store') }}" class="contract-form panel">
        @csrf
        <div class="panel-header">
            <span class="panel-title">Datos del contrato</span>
            <span class="badge badge-neutral">Borrador inicial</span>
        </div>
        <div class="panel-body">
            <div class="form-grid">
                <label class="field">
                    <span>Fecha del contrato</span>
                    <input type="date" name="contract_date" value="{{ old('contract_date', $contractDate) }}" required>
                </label>
                <label class="field">
                    <span>Cliente / empresa</span>
                    <input type="text" name="client_company_name" value="{{ old('client_company_name') }}" placeholder="Inversiones GACOV S.A.S." required>
                </label>
                <label class="field">
                    <span>NIT del cliente</span>
                    <input type="text" name="client_document" value="{{ old('client_document') }}" placeholder="900.983.146-..." required>
                </label>
                <label class="field">
                    <span>Representante legal</span>
                    <input type="text" name="client_legal_representative" value="{{ old('client_legal_representative') }}" placeholder="Nombre completo" required>
                </label>
                <label class="field">
                    <span>Documento representante</span>
                    <input type="text" name="client_legal_representative_document" value="{{ old('client_legal_representative_document') }}" placeholder="C.C.">
                </label>
                <label class="field">
                    <span>Correo de firma</span>
                    <input type="email" name="client_email" value="{{ old('client_email') }}" placeholder="correo@cliente.com" required>
                </label>
                <label class="field">
                    <span>Teléfono</span>
                    <input type="text" name="client_phone" value="{{ old('client_phone') }}" placeholder="+57 ...">
                </label>
                <label class="field field--full">
                    <span>Dirección</span>
                    <input type="text" name="client_address" value="{{ old('client_address') }}" placeholder="Dirección del cliente">
                </label>
            </div>

            <div class="section-divider"></div>

            <div class="form-grid">
                <label class="field">
                    <span>Nombre del prestador</span>
                    <input type="text" name="provider_name" value="{{ old('provider_name', $defaults['provider_name']) }}" required>
                </label>
                <label class="field">
                    <span>Documento del prestador</span>
                    <input type="text" name="provider_document" value="{{ old('provider_document', $defaults['provider_document']) }}" placeholder="Opcional">
                </label>
                <label class="field">
                    <span>Correo del prestador</span>
                    <input type="email" name="provider_email" value="{{ old('provider_email', $defaults['provider_email']) }}">
                </label>
                <label class="field">
                    <span>Teléfono del prestador</span>
                    <input type="text" name="provider_phone" value="{{ old('provider_phone', $defaults['provider_phone']) }}">
                </label>
                <label class="field field--full">
                    <span>Dirección del prestador</span>
                    <input type="text" name="provider_address" value="{{ old('provider_address', $defaults['provider_address']) }}">
                </label>
                <label class="field">
                    <span>Banco</span>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $defaults['bank_name']) }}" required>
                </label>
                <label class="field">
                    <span>Tipo de cuenta</span>
                    <input type="text" name="bank_account_type" value="{{ old('bank_account_type', $defaults['bank_account_type']) }}" required>
                </label>
                <label class="field">
                    <span>Número de cuenta</span>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $defaults['bank_account_number']) }}" required>
                </label>
                <label class="field field--full">
                    <span>Titular de la cuenta</span>
                    <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $defaults['bank_account_holder']) }}" required>
                </label>
            </div>

            <label class="field field--full">
                <span>Resumen comercial</span>
                <textarea name="summary" rows="4" placeholder="Resumen breve del alcance, fases o condiciones">{{ old('summary') }}</textarea>
            </label>

            <label class="field field--full">
                <span>Notas internas</span>
                <textarea name="client_notes" rows="3" placeholder="Observaciones adicionales">{{ old('client_notes') }}</textarea>
            </label>

            <div class="contract-actions">
                <a href="{{ route('contracts.index') }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar y enviar enlace</button>
            </div>
        </div>
    </form>

    <aside class="contract-side panel">
        <div class="panel-header">
            <span class="panel-title">Cómo funciona</span>
        </div>
        <div class="panel-body">
            <div class="side-step">
                <strong>1. Diligencias la data</strong>
                <p>Completa los datos del cliente, prestador y cuenta bancaria.</p>
            </div>
            <div class="side-step">
                <strong>2. Se envía el enlace</strong>
                <p>El sistema genera un enlace seguro para que el cliente firme en su celular o computador.</p>
            </div>
            <div class="side-step">
                <strong>3. Firma y bloqueo</strong>
                <p>Cuando el cliente firma, el contrato se bloquea, se genera el PDF y se envía la copia a ambas partes.</p>
            </div>
            <div class="side-note">
                El logo, los colores y la estructura del documento respetan la marca AMR Tech / GACOV.
            </div>
        </div>
    </aside>
</div>

@push('styles')
<style>
.contract-create-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(280px, .9fr);
    gap: 18px;
    align-items: start;
}

.contract-form .panel-body,
.contract-side .panel-body {
    padding: 18px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 13px;
    color: var(--gacov-text-secondary);
}

.field--full {
    grid-column: 1 / -1;
}

.field input,
.field textarea {
    width: 100%;
    border: 1px solid var(--gacov-border);
    border-radius: 14px;
    background: var(--gacov-bg-surface);
    color: var(--gacov-text-primary);
    padding: 12px 14px;
    font-size: 14px;
}

.field textarea {
    resize: vertical;
}

.section-divider {
    height: 1px;
    background: var(--gacov-border);
    margin: 18px 0;
}

.contract-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 18px;
}

.side-step + .side-step {
    margin-top: 16px;
}

.side-step strong {
    display: block;
    margin-bottom: 4px;
    color: var(--gacov-text-primary);
}

.side-step p {
    margin: 0;
    color: var(--gacov-text-secondary);
    line-height: 1.6;
}

.side-note {
    margin-top: 18px;
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(239, 68, 68, .08);
    border: 1px solid rgba(239, 68, 68, .16);
    color: var(--gacov-text-secondary);
}

@media (max-width: 1080px) {
    .contract-create-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
@endsection
