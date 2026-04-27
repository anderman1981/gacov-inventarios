@extends('layouts.app')

@section('title', 'Nueva Factura')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('invoices.index') }}" class="back-link">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
            Volver a facturas
        </a>
        <h1>Nueva Factura</h1>
        <p class="page-subtitle">Crear factura con numeración automática</p>
    </div>
</div>

<form action="{{ route('invoices.store') }}" method="POST" id="invoice-form" class="invoice-form">
    @csrf

    <div class="form-layout">
        {{-- Left Column: Issuer & Client --}}
        <div class="form-column">
            <div class="form-section">
                <h3>Datos del Emisor</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="issuer_name">Nombre / Razón Social *</label>
                        <input type="text" name="issuer_name" id="issuer_name" 
                               value="{{ old('issuer_name', $issuer['name'] ?? '') }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="issuer_nit">NIT / Cédula *</label>
                        <input type="text" name="issuer_nit" id="issuer_nit" 
                               value="{{ old('issuer_nit', $issuer['nit'] ?? '') }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="issuer_phone">Teléfono</label>
                        <input type="text" name="issuer_phone" id="issuer_phone" 
                               value="{{ old('issuer_phone', $issuer['phone'] ?? '') }}" class="form-input">
                    </div>
                    <div class="form-group full-width">
                        <label for="issuer_address">Dirección</label>
                        <input type="text" name="issuer_address" id="issuer_address" 
                               value="{{ old('issuer_address', $issuer['address'] ?? '') }}" class="form-input">
                    </div>
                    <div class="form-group full-width">
                        <label for="issuer_email">Email</label>
                        <input type="email" name="issuer_email" id="issuer_email" 
                               value="{{ old('issuer_email', $issuer['email'] ?? '') }}" class="form-input">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Datos del Cliente</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="client_name">Nombre / Razón Social *</label>
                        <input type="text" name="client_name" id="client_name" 
                               value="{{ old('client_name') }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="client_nit">NIT / Cédula *</label>
                        <input type="text" name="client_nit" id="client_nit" 
                               value="{{ old('client_nit') }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="client_phone">Teléfono</label>
                        <input type="text" name="client_phone" id="client_phone" 
                               value="{{ old('client_phone') }}" class="form-input">
                    </div>
                    <div class="form-group full-width">
                        <label for="client_address">Dirección</label>
                        <input type="text" name="client_address" id="client_address" 
                               value="{{ old('client_address') }}" class="form-input">
                    </div>
                    <div class="form-group full-width">
                        <label for="client_email">Email</label>
                        <input type="email" name="client_email" id="client_email" 
                               value="{{ old('client_email') }}" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Dates & Tax --}}
        <div class="form-column">
            <div class="form-section">
                <h3>Información de Factura</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="prefix">Prefijo</label>
                        <input type="text" name="prefix" id="prefix" value="{{ old('prefix', 'INV') }}" 
                               maxlength="10" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Número</label>
                        <div class="form-display">{{ $nextNumber }}</div>
                    </div>
                    <div class="form-group">
                        <label for="issue_date">Fecha de Emisión *</label>
                        <input type="date" name="issue_date" id="issue_date" 
                               value="{{ old('issue_date', date('Y-m-d')) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="due_date">Fecha de Vencimiento</label>
                        <input type="date" name="due_date" id="due_date" 
                               value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="tax_rate">Tasa IVA (%)</label>
                        <select name="tax_rate" id="tax_rate" class="form-input">
                            <option value="0">0%</option>
                            <option value="5">5%</option>
                            <option value="19" selected>19%</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="discount_amount">Descuento Total ($)</label>
                        <input type="number" name="discount_amount" id="discount_amount" 
                               value="{{ old('discount_amount', 0) }}" min="0" step="0.01" 
                               class="form-input">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Notas (Opcional)</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="notes">Notas</label>
                        <textarea name="notes" id="notes" rows="3" class="form-input">{{ old('notes') }}</textarea>
                    </div>
                    <div class="form-group full-width">
                        <label for="terms">Términos y Condiciones</label>
                        <textarea name="terms" id="terms" rows="2" class="form-input">{{ old('terms') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Items --}}
    <div class="form-section items-section">
        <div class="section-header">
            <h3>Conceptos / Ítems</h3>
            <button type="button" class="btn btn-secondary" id="add-item-btn">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                Agregar ítem
            </button>
        </div>

        <table class="items-table" id="items-table">
            <thead>
                <tr>
                    <th style="width: 40%">Descripción *</th>
                    <th style="width: 10%">Und</th>
                    <th style="width: 10%">Cantidad *</th>
                    <th style="width: 15%">Precio Unit. *</th>
                    <th style="width: 8%">Desc %</th>
                    <th style="width: 8%">IVA %</th>
                    <th style="width: 12%">Total</th>
                    <th style="width: 5%"></th>
                </tr>
            </thead>
            <tbody id="items-body">
                {{-- Items will be added dynamically --}}
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="5" class="text-right totals-label">Subtotal:</td>
                    <td class="text-right" id="subtotal-display">$0.00</td>
                    <td></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="5" class="text-right totals-label">Descuento:</td>
                    <td class="text-right" id="discount-display">-$0.00</td>
                    <td></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="5" class="text-right totals-label">IVA:</td>
                    <td class="text-right" id="tax-display">$0.00</td>
                    <td></td>
                </tr>
                <tr class="totals-row total-final-row">
                    <td colspan="5" class="text-right totals-label">TOTAL:</td>
                    <td class="text-right total-final" id="total-display">$0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        @error('items')
        <p class="form-error">{{ $message }}</p>
        @enderror
        @error('items.*.description')
        <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-actions">
        <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Guardar Factura
        </button>
    </div>
</form>

{{-- Item Template --}}
<template id="item-template">
    <tr class="item-row">
        <td>
            <input type="text" name="items[{index}][description]" class="item-description" required>
            <input type="hidden" name="items[{index}][product_key]" class="item-product-key">
        </td>
        <td>
            <input type="text" name="items[{index}][unit]" value="UN" class="item-unit" maxlength="20">
        </td>
        <td>
            <input type="number" name="items[{index}][quantity]" value="1" min="0.0001" step="0.01" 
                   class="item-quantity" required>
        </td>
        <td>
            <input type="number" name="items[{index}][unit_price]" value="0" min="0" step="0.01" 
                   class="item-price" required>
        </td>
        <td>
            <input type="number" name="items[{index}][discount_rate]" value="0" min="0" max="100" 
                   step="0.01" class="item-discount">
        </td>
        <td>
            <input type="number" name="items[{index}][tax_rate]" value="19" min="0" max="100" 
                   step="0.01" class="item-tax">
        </td>
        <td class="item-total text-right">$0.00</td>
        <td>
            <button type="button" class="btn-remove-item" title="Eliminar">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsBody = document.getElementById('items-body');
    const addItemBtn = document.getElementById('add-item-btn');
    const itemTemplate = document.getElementById('item-template');
    const discountInput = document.getElementById('discount_amount');
    const taxRateSelect = document.getElementById('tax_rate');
    let itemIndex = 0;

    function addItem() {
        const html = itemTemplate.innerHTML.replace(/{index}/g, itemIndex);
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = html;
        itemsBody.appendChild(tr);
        itemIndex++;
        attachItemEvents(tr);
    }

    function attachItemEvents(row) {
        const quantityInput = row.querySelector('.item-quantity');
        const priceInput = row.querySelector('.item-price');
        const discountInput = row.querySelector('.item-discount');
        const taxInput = row.querySelector('.item-tax');
        const removeBtn = row.querySelector('.btn-remove-item');

        [quantityInput, priceInput, discountInput, taxInput].forEach(input => {
            input.addEventListener('input', calculateTotals);
        });

        removeBtn.addEventListener('click', function() {
            row.remove();
            calculateTotals();
        });
    }

    function calculateTotals() {
        let subtotal = 0;
        let totalTax = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
            const tax = parseFloat(row.querySelector('.item-tax').value) || 0;

            const lineSubtotal = quantity * price;
            const discountAmount = lineSubtotal * (discount / 100);
            const subtotalAfterDiscount = lineSubtotal - discountAmount;
            const taxAmount = subtotalAfterDiscount * (tax / 100);
            const lineTotal = subtotalAfterDiscount + taxAmount;

            row.querySelector('.item-total').textContent = '$' + lineTotal.toFixed(2);

            subtotal += subtotalAfterDiscount;
            totalTax += taxAmount;
        });

        const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
        const grandTotal = subtotal + totalTax - discountAmount;

        document.getElementById('subtotal-display').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('discount-display').textContent = '-$' + discountAmount.toFixed(2);
        document.getElementById('tax-display').textContent = '$' + totalTax.toFixed(2);
        document.getElementById('total-display').textContent = '$' + grandTotal.toFixed(2);
    }

    addItemBtn.addEventListener('click', addItem);
    discountInput.addEventListener('input', calculateTotals);

    // Add initial item
    addItem();
});
</script>
@endpush

@push('styles')
<style>
.back-link { 
    display: inline-flex; align-items: center; gap: 6px; 
    color: var(--amr-text-muted, #9CA3AF); font-size: 14px; 
    text-decoration: none; margin-bottom: 8px; 
}
.back-link:hover { color: var(--amr-primary, #00D4FF); }

.invoice-form { }

.form-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.form-column { display: flex; flex-direction: column; gap: 24px; }

.form-section { background: var(--amr-bg-surface, #111827); border-radius: 12px; padding: 24px; }
.form-section h3 { font-size: 14px; font-weight: 600; color: var(--amr-text-primary, #F9FAFB); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; }
.form-group.full-width { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 500; color: var(--amr-text-muted, #9CA3AF); margin-bottom: 6px; }
.form-input { 
    padding: 10px 12px; background: rgba(255,255,255,0.05); 
    border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; 
    color: var(--amr-text-primary, #F9FAFB); font-size: 14px; width: 100%;
}
.form-input:focus { outline: none; border-color: var(--amr-primary, #00D4FF); }
.form-display { padding: 10px 12px; background: rgba(0,212,255,0.1); border: 1px solid rgba(0,212,255,0.2); border-radius: 8px; color: var(--amr-primary, #00D4FF); font-weight: 600; font-family: 'JetBrains Mono', monospace; }

.items-section { background: var(--amr-bg-surface, #111827); border-radius: 12px; padding: 24px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.section-header h3 { font-size: 14px; font-weight: 600; color: var(--amr-text-primary, #F9FAFB); margin: 0; }

.items-table { width: 100%; border-collapse: collapse; }
.items-table th { 
    font-size: 11px; font-weight: 600; color: var(--amr-text-muted, #9CA3AF); 
    text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 8px;
    background: rgba(255,255,255,0.03); text-align: left;
}
.items-table td { padding: 8px; vertical-align: middle; }
.items-table input { 
    width: 100%; padding: 8px 10px; background: rgba(255,255,255,0.05); 
    border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; 
    color: var(--amr-text-primary, #F9FAFB); font-size: 13px;
}
.items-table input:focus { outline: none; border-color: var(--amr-primary, #00D4FF); }
.item-total { font-weight: 600; color: var(--amr-primary, #00D4FF); min-width: 100px; }
.btn-remove-item { 
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    background: transparent; border: none; color: #9CA3AF; cursor: pointer;
}
.btn-remove-item:hover { background: rgba(239,68,68,0.1); color: #F87171; }
.btn-remove-item svg { width: 16px; height: 16px; }

.totals-row td { padding: 8px; font-size: 14px; }
.totals-label { color: var(--amr-text-muted, #9CA3AF); }
.total-final-row { border-top: 2px solid var(--amr-primary, #00D4FF); }
.total-final-row td { font-size: 18px; font-weight: 700; color: var(--amr-primary, #00D4FF); }

.form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
.form-error { color: #F87171; font-size: 13px; margin-top: 8px; }

@media (max-width: 900px) {
    .form-layout { grid-template-columns: 1fr; }
    .form-grid { grid-template-columns: 1fr; }
    .form-group.full-width { grid-column: 1; }
}
</style>
@endpush
