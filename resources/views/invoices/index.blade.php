@extends('layouts.app')

@section('title', 'Facturas')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Facturas</h1>
        <p class="page-subtitle">Gestión de facturas formales con soporte DIAN</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Nueva Factura
        </a>
    </div>
</div>

{{-- Stats Cards --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-total">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ number_format($stats['total']) }}</span>
            <span class="stat-label">Total Facturas</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-draft">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['draft'] }}</span>
            <span class="stat-label">Borradores</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-issued">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['issued'] }}</span>
            <span class="stat-label">Emitidas</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-paid">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ number_format($stats['paid'], 0) }}</span>
            <span class="stat-label">Pagadas</span>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="filter-bar">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <input type="text" name="search" placeholder="Buscar por número, cliente..." 
                   value="{{ request('search') }}" class="filter-input">

            <select name="status" class="filter-select">
                <option value="">Todos los estados</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Emitida</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Pagada</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
            </select>

            <select name="payment_status" class="filter-select">
                <option value="">Todos los pagos</option>
                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Parcial</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Pagado</option>
                <option value="overdue" {{ request('payment_status') === 'overdue' ? 'selected' : '' }}>Vencido</option>
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-input filter-date">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-input filter-date">

            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Limpiar</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Número</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Pago</th>
                <th class="text-right">Total</th>
                <th class="text-right">Saldo</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td>
                    <a href="{{ route('invoices.show', $invoice) }}" class="invoice-link">
                        <strong>{{ $invoice->full_number }}</strong>
                    </a>
                </td>
                <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                <td>
                    <div class="client-info">
                        <span class="client-name">{{ $invoice->client_name }}</span>
                        <span class="client-nit">{{ $invoice->client_nit }}</span>
                    </div>
                </td>
                <td>
                    <span class="status-badge status-{{ $invoice->status }}">
                        @switch($invoice->status)
                            @case('draft') Borrador @break
                            @case('issued') Emitida @break
                            @case('paid') Pagada @break
                            @case('cancelled') Cancelada @break
                            @case('expired') Vencida @break
                        @endswitch
                    </span>
                </td>
                <td>
                    <span class="payment-badge payment-{{ $invoice->payment_status }}">
                        @switch($invoice->payment_status)
                            @case('pending') Pendiente @break
                            @case('partial') Parcial @break
                            @case('paid') Pagado @break
                            @case('overdue') Vencido @break
                        @endswitch
                    </span>
                </td>
                <td class="text-right amount">${{ number_format($invoice->total, 2) }}</td>
                <td class="text-right amount {{ $invoice->balance_due > 0 ? 'balance-due' : 'balance-paid' }}">
                    ${{ number_format($invoice->balance_due, 2) }}
                </td>
                <td class="text-center">
                    <div class="action-buttons">
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn-icon" title="Ver">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"/></svg>
                        </a>
                        @if($invoice->status === 'draft')
                        <a href="{{ route('invoices.edit', $invoice) }}" class="btn-icon" title="Editar">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                        </a>
                        @endif
                        @if($invoice->status === 'issued')
                        <form action="{{ route('invoices.issue', $invoice) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn-icon" title="Descargar PDF">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                    </div>
                    <p>No hay facturas registradas</p>
                    <a href="{{ route('invoices.create') }}" class="btn btn-primary">Crear primera factura</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($invoices->hasPages())
<div class="pagination-wrapper">
    {{ $invoices->withQueryString()->links() }}
</div>
@endif
@endsection

@push('styles')
<style>
.invoice-link { color: var(--amr-primary, #00D4FF); text-decoration: none; }
.invoice-link:hover { text-decoration: underline; }

.client-info { display: flex; flex-direction: column; }
.client-name { font-weight: 500; color: var(--amr-text-primary, #F9FAFB); }
.client-nit { font-size: 12px; color: var(--amr-text-muted, #9CA3AF); }

.status-badge {
    display: inline-flex; padding: 4px 10px; border-radius: 9999px;
    font-size: 12px; font-weight: 600; text-transform: uppercase;
}
.status-draft { background: #374151; color: #9CA3AF; }
.status-issued { background: #1E3A5F; color: #60A5FA; }
.status-paid { background: #064E3B; color: #34D399; }
.status-cancelled { background: #7F1D1D; color: #F87171; }
.status-expired { background: #78350F; color: #FBBF24; }

.payment-badge {
    display: inline-flex; padding: 4px 10px; border-radius: 9999px;
    font-size: 11px; font-weight: 500;
}
.payment-pending { background: #78350F; color: #FBBF24; }
.payment-partial { background: #1E3A5F; color: #60A5FA; }
.payment-paid { background: #064E3B; color: #34D399; }
.payment-overdue { background: #7F1D1D; color: #F87171; }

.amount { font-family: 'JetBrains Mono', monospace; }
.balance-due { color: #F87171; font-weight: 600; }
.balance-paid { color: #34D399; }

.action-buttons { display: flex; gap: 4px; justify-content: center; }
.btn-icon {
    display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 6px;
    background: transparent; border: 1px solid rgba(255,255,255,0.1);
    color: #9CA3AF; cursor: pointer; transition: all 0.2s;
}
.btn-icon:hover { background: rgba(255,255,255,0.05); color: #F9FAFB; border-color: rgba(255,255,255,0.2); }
.btn-icon svg { width: 16px; height: 16px; }

.inline { display: inline; }

.empty-state { text-align: center; padding: 48px 24px !important; }
.empty-icon { width: 64px; height: 64px; margin: 0 auto 16px; opacity: 0.3; }
.empty-icon svg { width: 100%; height: 100%; }
.empty-state p { color: var(--amr-text-muted, #9CA3AF); margin-bottom: 16px; }
</style>
@endpush
