@extends('layouts.app')

@section('title', "Factura {$invoice->full_number}")

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('invoices.index') }}" class="back-link">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
            Volver a facturas
        </a>
        <h1>Factura {{ $invoice->full_number }}</h1>
        <div class="invoice-meta">
            <span class="status-badge status-{{ $invoice->status }}">{{ $invoice->status_label }}</span>
            <span class="payment-badge payment-{{ $invoice->payment_status }}">{{ $invoice->payment_status_label }}</span>
        </div>
    </div>
    <div class="page-header-actions">
        @if($invoice->status === 'draft')
        <form action="{{ route('invoices.issue', $invoice) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="btn btn-success">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                Emitir Factura
            </button>
        </form>
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
            Editar
        </a>
        @endif
        @if($invoice->status === 'issued')
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-secondary">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            Descargar PDF
        </a>
        @endif
        @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
        <form action="{{ route('invoices.cancel', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('¿Cancelar esta factura?');">
            @csrf
            <button type="submit" class="btn btn-danger">Cancelar</button>
        </form>
        @endif
    </div>
</div>

<div class="invoice-layout">
    {{-- Header --}}
    <div class="invoice-header">
        <div class="issuer-section">
            <div class="company-logo">
                <span>GACOV</span>
            </div>
            <h2>{{ $invoice->issuer_name }}</h2>
            <p>NIT: {{ $invoice->issuer_nit }}</p>
            @if($invoice->issuer_address)
            <p>{{ $invoice->issuer_address }}</p>
            @endif
            @if($invoice->issuer_phone)
            <p>Tel: {{ $invoice->issuer_phone }}</p>
            @endif
            @if($invoice->issuer_email)
            <p>{{ $invoice->issuer_email }}</p>
            @endif
        </div>

        <div class="invoice-info">
            <h3>FACTURA DE VENTA</h3>
            <div class="invoice-number">{{ $invoice->full_number }}</div>
            <table class="info-table">
                <tr>
                    <td>Fecha de emisión:</td>
                    <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                </tr>
                @if($invoice->due_date)
                <tr>
                    <td>Fecha de vencimiento:</td>
                    <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                </tr>
                @endif
                @if($invoice->paid_at)
                <tr>
                    <td>Fecha de pago:</td>
                    <td class="text-success">{{ $invoice->paid_at->format('d/m/Y') }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- Cliente --}}
    <div class="invoice-client">
        <h4>CLIENTE</h4>
        <div class="client-details">
            <p class="client-name">{{ $invoice->client_name }}</p>
            <p>NIT: {{ $invoice->client_nit }}</p>
            @if($invoice->client_address)
            <p>{{ $invoice->client_address }}</p>
            @endif
            @if($invoice->client_email)
            <p>{{ $invoice->client_email }}</p>
            @endif
            @if($invoice->client_phone)
            <p>Tel: {{ $invoice->client_phone }}</p>
            @endif
        </div>
    </div>

    {{-- Items --}}
    <div class="invoice-items">
        <table class="items-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="text-center">Und</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Desc.</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">IVA</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-desc">{{ $item->description }}</div>
                        @if($item->product_key)
                        <div class="item-key">Código: {{ $item->product_key }}</div>
                        @endif
                        @if($item->billing_period)
                        <div class="item-period">Período: {{ $item->billing_period }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ $item->discount_rate > 0 ? $item->discount_rate . '%' : '-' }}</td>
                    <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                    <td class="text-right">{{ $item->tax_rate }}%</td>
                    <td class="text-right item-total">${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="invoice-totals">
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">${{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td>Descuento:</td>
                    <td class="text-right">-${{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>IVA ({{ $invoice->tax_rate }}%):</td>
                    <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="total-final">
                    <td>TOTAL:</td>
                    <td class="text-right">${{ number_format($invoice->total, 2) }}</td>
                </tr>
                @if($invoice->paid_amount > 0)
                <tr class="paid-row">
                    <td>Pagado:</td>
                    <td class="text-right text-success">-${{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="balance-row">
                    <td>SALDO PENDIENTE:</td>
                    <td class="text-right {{ $invoice->balance_due > 0 ? 'text-danger' : 'text-success' }}">
                        ${{ number_format($invoice->balance_due, 2) }}
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- Notes --}}
    @if($invoice->notes || $invoice->terms)
    <div class="invoice-notes">
        @if($invoice->notes)
        <div class="notes-section">
            <h4>Notas</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif
        @if($invoice->terms)
        <div class="terms-section">
            <h4>Términos y Condiciones</h4>
            <p>{{ $invoice->terms }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- DIAN --}}
    @if($invoice->dian_sequential_code)
    <div class="dian-section">
        <h4>Información DIAN</h4>
        <div class="dian-grid">
            <div>
                <span class="dian-label">Código de autorización:</span>
                <span class="dian-value">{{ $invoice->dian_sequential_code }}</span>
            </div>
            @if($invoice->dian_resolution_number)
            <div>
                <span class="dian-label">Número de resolución:</span>
                <span class="dian-value">{{ $invoice->dian_resolution_number }}</span>
            </div>
            @endif
            @if($invoice->dian_from_date)
            <div>
                <span class="dian-label">Vigencia:</span>
                <span class="dian-value">{{ $invoice->dian_from_date->format('d/m/Y') }} - {{ $invoice->dian_to_date?->format('d/m/Y') }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Payments --}}
    @if($invoice->payments->count() > 0)
    <div class="payments-section">
        <h4>Pagos Registrados</h4>
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th>Referencia</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td>{{ $payment->method_label }}</td>
                    <td>{{ $payment->reference ?? '-' }}</td>
                    <td class="text-right">${{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Register Payment --}}
    @if($invoice->status === 'issued' && $invoice->balance_due > 0)
    <div class="register-payment">
        <h4>Registrar Pago</h4>
        <form action="{{ route('invoices.payments.store', $invoice) }}" method="POST" class="payment-form">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label for="amount">Monto</label>
                    <input type="number" name="amount" id="amount" step="0.01" 
                           max="{{ $invoice->balance_due }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label for="payment_date">Fecha de pago</label>
                    <input type="date" name="payment_date" id="payment_date" 
                           value="{{ date('Y-m-d') }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label for="payment_method">Método de pago</label>
                    <select name="payment_method" id="payment_method" required class="form-input">
                        <option value="transferencia">Transferencia</option>
                        <option value="pse">PSE</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reference">Referencia (opcional)</label>
                    <input type="text" name="reference" id="reference" class="form-input">
                </div>
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-success">Registrar Pago</button>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.back-link { 
    display: inline-flex; align-items: center; gap: 6px; 
    color: var(--amr-text-muted, #9CA3AF); font-size: 14px; 
    text-decoration: none; margin-bottom: 8px; 
}
.back-link:hover { color: var(--amr-primary, #00D4FF); }

.invoice-meta { display: flex; gap: 8px; margin-top: 8px; }

.invoice-layout { background: var(--amr-bg-surface, #111827); border-radius: 12px; padding: 32px; }

.invoice-header { display: flex; justify-content: space-between; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }

.issuer-section { max-width: 300px; }
.company-logo { 
    width: 60px; height: 60px; background: linear-gradient(135deg, #00D4FF, #7C3AED); 
    border-radius: 12px; display: flex; align-items: center; justify-content: center; 
    font-weight: 700; font-size: 18px; color: white; margin-bottom: 12px; 
}
.issuer-section h2 { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
.issuer-section p { font-size: 13px; color: var(--amr-text-muted, #9CA3AF); margin: 0; }

.invoice-info { text-align: right; }
.invoice-info h3 { font-size: 14px; font-weight: 600; color: var(--amr-text-muted, #9CA3AF); letter-spacing: 2px; margin-bottom: 8px; }
.invoice-number { font-size: 24px; font-weight: 700; color: var(--amr-primary, #00D4FF); margin-bottom: 16px; }
.info-table { margin-left: auto; }
.info-table td:first-child { color: var(--amr-text-muted, #9CA3AF); padding-right: 16px; }
.info-table td { font-size: 14px; padding: 4px 0; }

.invoice-client { margin-bottom: 24px; }
.invoice-client h4 { font-size: 11px; font-weight: 600; color: var(--amr-text-muted, #9CA3AF); letter-spacing: 1px; margin-bottom: 8px; }
.client-name { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
.client-details p { margin: 0; font-size: 14px; color: var(--amr-text-muted, #9CA3AF); }

.invoice-items { margin-bottom: 24px; }
.items-table { width: 100%; border-collapse: collapse; }
.items-table th { 
    background: rgba(255,255,255,0.05); padding: 12px 8px; 
    font-size: 11px; font-weight: 600; color: var(--amr-text-muted, #9CA3AF); 
    text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.1);
}
.items-table td { padding: 16px 8px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: top; }
.item-desc { font-weight: 500; }
.item-key, .item-period { font-size: 12px; color: var(--amr-text-muted, #9CA3AF); margin-top: 4px; }
.item-total { font-weight: 600; color: var(--amr-primary, #00D4FF); }

.invoice-totals { display: flex; justify-content: flex-end; margin-bottom: 24px; }
.totals-table { min-width: 280px; }
.totals-table td { padding: 8px 0; font-size: 14px; }
.totals-table td:last-child { min-width: 120px; text-align: right; }
.total-final { font-size: 18px; font-weight: 700; border-top: 2px solid var(--amr-primary, #00D4FF); padding-top: 12px !important; }
.total-final td { color: var(--amr-primary, #00D4FF); }
.paid-row td { color: #34D399; }
.balance-row td { font-size: 16px; font-weight: 600; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 12px !important; }
.text-success { color: #34D399; }
.text-danger { color: #F87171; }

.invoice-notes { display: flex; gap: 24px; margin-bottom: 24px; padding: 16px; background: rgba(255,255,255,0.03); border-radius: 8px; }
.notes-section, .terms-section { flex: 1; }
.notes-section h4, .terms-section h4 { font-size: 12px; font-weight: 600; color: var(--amr-text-muted, #9CA3AF); margin-bottom: 8px; }
.notes-section p, .terms-section p { font-size: 13px; color: var(--amr-text-secondary, #D1D5DB); margin: 0; white-space: pre-wrap; }

.dian-section { margin-bottom: 24px; padding: 16px; background: rgba(0,212,255,0.05); border: 1px solid rgba(0,212,255,0.1); border-radius: 8px; }
.dian-section h4 { font-size: 12px; font-weight: 600; color: var(--amr-primary, #00D4FF); margin-bottom: 12px; }
.dian-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
.dian-label { font-size: 11px; color: var(--amr-text-muted, #9CA3AF); display: block; }
.dian-value { font-size: 14px; font-weight: 500; }

.payments-section { margin-bottom: 24px; }
.payments-section h4 { font-size: 14px; font-weight: 600; margin-bottom: 12px; }
.payments-table { width: 100%; border-collapse: collapse; }
.payments-table th { font-size: 11px; font-weight: 600; color: var(--amr-text-muted, #9CA3AF); padding: 8px; text-align: left; }
.payments-table td { padding: 12px 8px; border-bottom: 1px solid rgba(255,255,255,0.05); }

.register-payment { padding: 24px; background: rgba(255,255,255,0.03); border-radius: 8px; }
.register-payment h4 { font-size: 14px; font-weight: 600; margin-bottom: 16px; }
.payment-form .form-row { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
.form-group { flex: 1; min-width: 150px; }
.form-group label { display: block; font-size: 12px; font-weight: 500; color: var(--amr-text-muted, #9CA3AF); margin-bottom: 6px; }
.form-input { width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: var(--amr-text-primary, #F9FAFB); font-size: 14px; }
.form-input:focus { outline: none; border-color: var(--amr-primary, #00D4FF); }
.form-actions { flex: 0; }

.inline { display: inline; }
</style>
@endpush
