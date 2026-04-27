@extends('layouts.app')

@section('title', "Contrato {$contract->contract_number}")

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('contracts.index') }}" class="back-link">← Contratos</a>
        <h1>{{ $contract->contract_number }}</h1>
        <p class="page-subtitle">Estado: <strong>{{ $contract->status_label }}</strong></p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('contracts.pdf', $contract) }}" class="btn btn-secondary">Descargar PDF</a>
        <button type="button" class="btn btn-secondary" data-copy-link="{{ $pdfUrl }}">Copiar enlace PDF</button>
        @if($signingUrl)
        <button type="button" class="btn btn-primary" data-copy-link="{{ $signingUrl }}">Copiar enlace de firma</button>
        <form method="POST" action="{{ route('contracts.resend-link', $contract) }}" class="inline">
            @csrf
            <button type="submit" class="btn btn-secondary">Reenviar enlace</button>
        </form>
        @endif
    </div>
</div>

<div class="contract-show-grid">
    <section class="panel">
        <div class="panel-header">
            <span class="panel-title">Resumen</span>
            <span class="badge {{ $contract->status === 'signed' ? 'badge-success' : 'badge-neutral' }}">
                {{ $contract->status_label }}
            </span>
        </div>
        <div class="panel-body">
            <div class="contract-summary-grid">
                <div><span>Cliente</span><strong>{{ $contract->client_company_name }}</strong></div>
                <div><span>Representante</span><strong>{{ $contract->client_legal_representative }}</strong></div>
                <div><span>Correo</span><strong>{{ $contract->client_email }}</strong></div>
                <div><span>Firma enviada</span><strong>{{ optional($contract->signature_link_sent_at)->format('d/m/Y H:i') ?? 'Pendiente' }}</strong></div>
            </div>
            @if($contract->client_signed_at)
            <div class="signed-banner">
                Firmado por {{ $contract->client_signer_name }} el {{ $contract->client_signed_at->format('d/m/Y H:i') }}.
            </div>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <span class="panel-title">Acciones rápidas</span>
        </div>
        <div class="panel-body">
            <div class="action-stack">
                @if($signingUrl)
                <a href="{{ $signingUrl }}" class="btn btn-primary">Abrir enlace de firma</a>
                @endif
                <a href="{{ route('contracts.create') }}" class="btn btn-secondary">Nuevo contrato</a>
            </div>
        </div>
    </section>
</div>

<section class="panel" style="margin-top:18px">
    <div class="panel-header">
        <span class="panel-title">Documento</span>
    </div>
    <div class="panel-body contract-doc-shell">
        @include('contracts.partials.document')
    </div>
</section>

@push('styles')
<style>
.contract-show-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.contract-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.contract-summary-grid div {
    padding: 14px;
    border-radius: 14px;
    background: var(--gacov-bg-surface);
    border: 1px solid var(--gacov-border);
}

.contract-summary-grid span {
    display: block;
    font-size: 12px;
    color: var(--gacov-text-muted);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.contract-summary-grid strong {
    color: var(--gacov-text-primary);
}

.signed-banner {
    margin-top: 16px;
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(16, 185, 129, .1);
    border: 1px solid rgba(16, 185, 129, .18);
    color: var(--gacov-text-primary);
}

.action-stack {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.page-header-actions form {
    display: inline-flex;
}

.contract-doc-shell {
    background: #fff;
    color: #111;
    border-radius: 18px;
    overflow: hidden;
}

@media (max-width: 1080px) {
    .contract-show-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .contract-summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-copy-link]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.getAttribute('data-copy-link');
            if (!url) return;
            try {
                await navigator.clipboard.writeText(url);
                button.textContent = 'Enlace copiado';
                setTimeout(() => { button.textContent = 'Copiar enlace de firma'; }, 1800);
            } catch (error) {
                console.error(error);
            }
        });
    });
});
</script>
@endpush
@endsection
