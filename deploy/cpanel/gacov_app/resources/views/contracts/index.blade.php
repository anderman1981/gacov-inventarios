@extends('layouts.app')

@section('title', 'Contratos')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Contratos interactivos</h1>
        <p class="page-subtitle">Gestiona el borrador, el enlace de firma y la copia final en PDF.</p>
    </div>
    <div class="page-header-actions">
        @if($latestContract)
        <a href="{{ route('contracts.show', $latestContract) }}" class="btn btn-secondary">Abrir último contrato</a>
        @endif
        <a href="{{ route('contracts.create') }}" class="btn btn-primary">Nuevo contrato</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-value">{{ $stats['total'] }}</span><span class="stat-label">Total</span></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-value">{{ $stats['draft'] }}</span><span class="stat-label">Borradores</span></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-value">{{ $stats['pending'] }}</span><span class="stat-label">Pendientes</span></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-value">{{ $stats['signed'] }}</span><span class="stat-label">Firmados</span></div></div>
</div>

<div class="panel">
    <div class="panel-header">
        <span class="panel-title">Listado</span>
        @if($latestContract)
        <span class="badge badge-neutral">Último: {{ $latestContract->contract_number }}</span>
        @endif
    </div>
    <div class="panel-body" style="padding:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contracts as $contract)
                <tr>
                    <td><a href="{{ route('contracts.show', $contract) }}">{{ $contract->contract_number }}</a></td>
                    <td>{{ $contract->contract_date->format('d/m/Y') }}</td>
                    <td>{{ $contract->client_company_name }}</td>
                    <td>{{ $contract->status_label }}</td>
                    <td>
                        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary" style="min-height:36px;padding:8px 12px">Ver</a>
                        @if($contract->pdf_path)
                        <a href="{{ route('contracts.pdf', $contract) }}" class="btn btn-secondary" style="min-height:36px;padding:8px 12px">PDF</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px;color:var(--gacov-text-muted)">
                        No hay contratos registrados todavía.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($contracts->hasPages())
<div class="pagination-wrapper">
    {{ $contracts->withQueryString()->links() }}
</div>
@endif
@endsection
