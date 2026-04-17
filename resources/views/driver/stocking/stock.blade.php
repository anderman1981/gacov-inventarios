@extends('layouts.app')
@section('title', 'Surtir máquina — Fase 3')

@section('content')
@php
    $routeQuery = $record->route_id ? ['route_id' => $record->route_id] : [];
    $billDenominations = \App\Models\MachineStockingRecord::BILL_DENOMINATIONS;
    $coinDenominations = \App\Models\MachineStockingRecord::COIN_DENOMINATIONS;
@endphp

<div class="page-header">
    <h1 class="page-title">Surtir la máquina</h1>
    <p class="page-subtitle">
        <a href="{{ route('driver.dashboard', $routeQuery) }}" style="color:var(--gacov-text-muted);text-decoration:none">Mi ruta</a>
        / Surtido <strong>{{ $record->code }}</strong> — Fase 3 de 3
    </p>
</div>

@if(session('error'))
<div class="alert alert-error" style="margin-bottom:var(--space-5)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    {{ session('error') }}
</div>
@endif

{{-- Progress stepper --}}
<div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-6);flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:var(--space-2)">
        <span style="width:28px;height:28px;border-radius:50%;background:var(--gacov-success);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700">✓</span>
        <span style="font-size:13px;color:var(--gacov-success);font-weight:600">Inspección</span>
    </div>
    <span style="color:var(--gacov-border);font-size:18px">→</span>
    <div style="display:flex;align-items:center;gap:var(--space-2)">
        <span style="width:28px;height:28px;border-radius:50%;background:var(--gacov-success);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700">✓</span>
        <span style="font-size:13px;color:var(--gacov-success);font-weight:600">Carga vehículo</span>
    </div>
    <span style="color:var(--gacov-border);font-size:18px">→</span>
    <div style="display:flex;align-items:center;gap:var(--space-2)">
        <span style="width:28px;height:28px;border-radius:50%;background:var(--gacov-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700">3</span>
        <span style="font-size:13px;color:var(--gacov-primary);font-weight:600">Surtir máquina</span>
    </div>
</div>

<form method="POST" action="{{ route('driver.stocking.complete', $record) }}" id="stocking-complete-form">
@csrf

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-5);align-items:start" class="stocking-stock-layout">

    {{-- Columna izquierda: productos + efectivo --}}
    <div style="display:flex;flex-direction:column;gap:var(--space-5)">

        {{-- Productos a cargar en la máquina --}}
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Productos — {{ $record->machine?->name }}</span>
                <span class="badge badge-success">{{ $items->count() }} producto{{ $items->count() === 1 ? '' : 's' }}</span>
            </div>
            <div class="panel-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Unidad</th>
                            <th style="text-align:center">Cantidad a surtir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product?->name ?? "Producto #{$item->product_id}" }}</strong>
                                @if($item->product?->code)
                                <div style="font-size:11px;color:var(--gacov-text-muted);margin-top:2px">{{ strtoupper($item->product->code) }}</div>
                                @endif
                            </td>
                            <td style="color:var(--gacov-text-muted)">{{ $item->product?->unit ?? '—' }}</td>
                            <td style="text-align:center">
                                <span class="badge badge-info" style="font-size:15px;font-weight:700;padding:6px 14px">
                                    {{ number_format($item->quantity_loaded, 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-6)">
                                No hay productos en este surtido.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Efectivo cargado --}}
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Efectivo cargado a la máquina</span>
                <span id="total-cash-badge" class="badge badge-neutral">$0</span>
            </div>
            <div class="panel-body">
                <p style="font-size:13px;color:var(--gacov-text-muted);margin-bottom:var(--space-5)">
                    Ingresa las cantidades de billetes y monedas que vas a dejar en la máquina. Deja en 0 los que no aplican.
                </p>

                {{-- Billetes --}}
                <div style="margin-bottom:var(--space-5)">
                    <div style="font-size:12px;font-weight:700;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-3)">
                        Billetes
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--space-3)">
                        @foreach($billDenominations as $field => $value)
                        <div style="background:var(--gacov-bg-elevated);border-radius:var(--radius-md);padding:var(--space-3)">
                            <label style="font-size:12px;color:var(--gacov-text-muted);display:block;margin-bottom:6px">
                                ${{ number_format($value, 0, ',', '.') }}
                            </label>
                            <div style="display:flex;align-items:center;gap:var(--space-2)">
                                <input type="number"
                                       name="{{ $field }}"
                                       id="{{ $field }}"
                                       value="{{ old($field, 0) }}"
                                       min="0"
                                       step="1"
                                       class="form-input denomination-input"
                                       data-denomination="{{ $value }}"
                                       data-type="bills"
                                       style="text-align:center;padding:6px 8px;width:70px;flex-shrink:0">
                                <span style="font-size:12px;color:var(--gacov-text-muted)">uds.</span>
                            </div>
                            <div style="font-size:11px;color:var(--gacov-text-secondary);margin-top:4px">
                                = <span class="denom-subtotal" data-field="{{ $field }}">$0</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div style="text-align:right;margin-top:var(--space-3);font-size:13px;color:var(--gacov-text-secondary)">
                        Subtotal billetes: <strong id="subtotal-bills" style="color:var(--gacov-text-primary)">$0</strong>
                    </div>
                </div>

                {{-- Monedas --}}
                <div>
                    <div style="font-size:12px;font-weight:700;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-3)">
                        Monedas
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:var(--space-3)">
                        @foreach($coinDenominations as $field => $value)
                        <div style="background:var(--gacov-bg-elevated);border-radius:var(--radius-md);padding:var(--space-3)">
                            <label style="font-size:12px;color:var(--gacov-text-muted);display:block;margin-bottom:6px">
                                ${{ number_format($value, 0, ',', '.') }}
                            </label>
                            <div style="display:flex;align-items:center;gap:var(--space-2)">
                                <input type="number"
                                       name="{{ $field }}"
                                       id="{{ $field }}"
                                       value="{{ old($field, 0) }}"
                                       min="0"
                                       step="1"
                                       class="form-input denomination-input"
                                       data-denomination="{{ $value }}"
                                       data-type="coins"
                                       style="text-align:center;padding:6px 8px;width:70px;flex-shrink:0">
                                <span style="font-size:12px;color:var(--gacov-text-muted)">uds.</span>
                            </div>
                            <div style="font-size:11px;color:var(--gacov-text-secondary);margin-top:4px">
                                = <span class="denom-subtotal" data-field="{{ $field }}">$0</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div style="text-align:right;margin-top:var(--space-3);font-size:13px;color:var(--gacov-text-secondary)">
                        Subtotal monedas: <strong id="subtotal-coins" style="color:var(--gacov-text-primary)">$0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Columna derecha: resumen + botón --}}
    <div style="position:sticky;top:var(--space-4)">
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Resumen del surtido</span>
            </div>
            <div class="panel-body">

                <div style="display:flex;flex-direction:column;gap:var(--space-3);margin-bottom:var(--space-5)">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:13px;color:var(--gacov-text-muted)">Máquina</span>
                        <span style="font-size:13px;font-weight:600">{{ $record->machine?->name }}</span>
                    </div>
                    @if($record->machine?->location)
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:13px;color:var(--gacov-text-muted)">Ubicación</span>
                        <span style="font-size:13px">{{ $record->machine->location }}</span>
                    </div>
                    @endif
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:13px;color:var(--gacov-text-muted)">Código</span>
                        <span style="font-family:var(--font-mono);font-size:12px;color:var(--gacov-primary)">{{ $record->code }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:13px;color:var(--gacov-text-muted)">Productos</span>
                        <span style="font-size:13px;font-weight:600">{{ $items->count() }} ítems</span>
                    </div>
                </div>

                <div style="background:var(--gacov-bg-elevated);border-radius:var(--radius-md);padding:var(--space-4);margin-bottom:var(--space-5)">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-2)">
                        <span style="font-size:13px;color:var(--gacov-text-muted)">Total billetes</span>
                        <span id="summary-bills" style="font-size:13px;font-weight:600">$0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3)">
                        <span style="font-size:13px;color:var(--gacov-text-muted)">Total monedas</span>
                        <span id="summary-coins" style="font-size:13px;font-weight:600">$0</span>
                    </div>
                    <div style="border-top:1px solid var(--gacov-border);padding-top:var(--space-3);display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:14px;font-weight:700">Total efectivo</span>
                        <span id="summary-total" style="font-size:18px;font-weight:700;color:var(--gacov-primary)">$0</span>
                    </div>
                </div>

                <button type="submit"
                        class="btn btn-primary"
                        style="width:100%;font-size:15px;padding:14px;font-weight:700"
                        id="btn-machine-stocked">
                    ✓ Máquina surtida
                </button>

                <p style="font-size:12px;color:var(--gacov-text-muted);text-align:center;margin-top:var(--space-3)">
                    Esta acción notificará al administrador y descontará el inventario del vehículo.
                </p>

                <a href="{{ route('driver.stocking.loading', $record) }}"
                   style="display:block;text-align:center;margin-top:var(--space-3);font-size:13px;color:var(--gacov-text-muted);text-decoration:none">
                    ← Volver a la lista de carga
                </a>
            </div>
        </div>
    </div>

</div>
</form>

<style>
@media (max-width: 900px) {
    .stocking-stock-layout {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const inputs = Array.from(document.querySelectorAll('.denomination-input'));

    function formatCOP(value) {
        return '$' + value.toLocaleString('es-CO', { maximumFractionDigits: 0 });
    }

    function recalculate() {
        let totalBills = 0;
        let totalCoins = 0;

        inputs.forEach((input) => {
            const qty = Math.max(0, parseInt(input.value || '0', 10));
            const denomination = parseInt(input.dataset.denomination || '0', 10);
            const subtotal = qty * denomination;
            const subtotalEl = document.querySelector(`.denom-subtotal[data-field="${input.name}"]`);

            if (subtotalEl) {
                subtotalEl.textContent = formatCOP(subtotal);
            }

            if (input.dataset.type === 'bills') {
                totalBills += subtotal;
            } else {
                totalCoins += subtotal;
            }
        });

        const total = totalBills + totalCoins;

        const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = formatCOP(val); };

        setEl('subtotal-bills', totalBills);
        setEl('subtotal-coins', totalCoins);
        setEl('summary-bills', totalBills);
        setEl('summary-coins', totalCoins);
        setEl('summary-total', total);

        const badge = document.getElementById('total-cash-badge');
        if (badge) {
            badge.textContent = formatCOP(total);
            badge.className = total > 0 ? 'badge badge-success' : 'badge badge-neutral';
        }
    }

    inputs.forEach((input) => {
        input.addEventListener('input', recalculate);
        input.addEventListener('change', recalculate);
    });

    recalculate();

    // Confirmar antes de enviar
    const form = document.getElementById('stocking-complete-form');
    form?.addEventListener('submit', (e) => {
        const totalEl = document.getElementById('summary-total');
        const total = totalEl ? totalEl.textContent : '$0';
        const confirmed = confirm(
            `¿Confirmás que surtiste la máquina?\n\nEfectivo cargado: ${total}\n\nEsta acción no se puede deshacer.`
        );

        if (!confirmed) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
