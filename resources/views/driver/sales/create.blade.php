@extends('layouts.app')
@section('title', 'Registrar venta')

@php
    $routeQuery = $route?->id ? ['route_id' => $route->id] : [];
@endphp

@section('content')
<div class="page-header">
    <h1 class="page-title">Registrar venta</h1>
    <p class="page-subtitle">
        <a href="{{ route('driver.dashboard', $routeQuery) }}" style="color:var(--gacov-text-muted);text-decoration:none">Mi ruta</a>
        / Registrar venta
    </p>
</div>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:var(--space-5)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div>
        <strong>No pudimos registrar la venta.</strong>
        <ul style="margin-top:6px;padding-left:18px;list-style:disc;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body">
        <form method="GET" action="{{ route('driver.sales.create') }}" class="driver-route-switcher" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-4);align-items:end">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="driver-sales-route-id">Seleccionar ruta</label>
                <select id="driver-sales-route-id" name="route_id" class="form-input" onchange="this.form.submit()">
                    <option value="">Seleccionar ruta...</option>
                    @foreach($availableRoutes as $availableRoute)
                        <option value="{{ $availableRoute->id }}" {{ $route?->id === $availableRoute->id ? 'selected' : '' }}>
                            {{ $availableRoute->code }} — {{ $availableRoute->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="driver-sales-machine-id">Seleccionar máquina</label>
                <select id="driver-sales-machine-id" name="machine_id" class="form-input" onchange="this.form.submit()" {{ $route ? '' : 'disabled' }}>
                    <option value="">Selecciona una máquina...</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->id }}" {{ $selectedMachine?->id === $machine->id ? 'selected' : '' }}>
                            {{ $machine->code }} — {{ $machine->name }}@if($machine->location) ({{ $machine->location }})@endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="driver-sales-stock-filter">Filtro de stock</label>
                <select id="driver-sales-stock-filter" name="stock_filter" class="form-input" onchange="this.form.submit()" {{ $selectedMachine ? '' : 'disabled' }}>
                    <option value="all" {{ $stockFilter === 'all' ? 'selected' : '' }}>Todos los productos</option>
                    <option value="with_stock" {{ $stockFilter === 'with_stock' ? 'selected' : '' }}>Con stock</option>
                    <option value="low_stock" {{ $stockFilter === 'low_stock' ? 'selected' : '' }}>Stock bajo</option>
                    <option value="empty" {{ $stockFilter === 'empty' ? 'selected' : '' }}>Sin stock</option>
                </select>
            </div>

            <noscript>
                <button type="submit" class="btn btn-primary" style="width:auto">Cargar selección</button>
            </noscript>
        </form>

        @if($selectedMachine)
            <div class="sale-machine-summary">
                <div class="sale-machine-summary__card">
                    <span class="sale-machine-summary__eyebrow">Máquina activa</span>
                    <strong>{{ $selectedMachine->code }} — {{ $selectedMachine->name }}</strong>
                    <span>{{ $selectedMachine->location ?: 'Sin ubicación' }}</span>
                </div>
                <div class="sale-machine-summary__card">
                    <span class="sale-machine-summary__eyebrow">Stock visible</span>
                    <strong>{{ number_format($selectedMachineStockTotal, 0, ',', '.') }} uds</strong>
                    <span>{{ number_format($selectedMachineAvailableSkus, 0, ',', '.') }} referencias con inventario</span>
                </div>
                <div class="sale-machine-summary__card">
                    <span class="sale-machine-summary__eyebrow">Ajuste comercial</span>
                    <strong>Precio editable por línea</strong>
                    <span>Costo y stock se muestran para vender con contexto real.</span>
                </div>
            </div>
        @else
            <div class="alert alert-info" style="margin-top:var(--space-4);margin-bottom:0">
                Selecciona una máquina para ver el inventario disponible, el costo de referencia y ajustar el precio por producto.
            </div>
        @endif
    </div>
</div>

<div class="panel" style="max-width:1100px">
    <div class="panel-header">
        <span class="panel-title">Registro de venta en máquina</span>
        <span class="badge badge-neutral">Precio editable, stock en contexto y notas por línea</span>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('driver.sales.store') }}" id="driver-sales-form">
            @csrf
            <input type="hidden" name="route_id" value="{{ $route?->id }}">
            <input type="hidden" name="machine_id" value="{{ $selectedMachine?->id }}">

            <div class="table-scroll sales-table-scroll">
                <table class="data-table sales-line-items-table">
                    <thead>
                        <tr>
                            <th>COD</th>
                            <th>Producto</th>
                            <th>CAT</th>
                            <th>Estado</th>
                            <th style="text-align:right">Costo</th>
                            <th style="text-align:right">Stock máquina</th>
                            <th style="text-align:right">Precio unit. (COP)</th>
                            <th>Observación</th>
                            <th style="text-align:center;width:120px">Cantidad</th>
                            <th style="text-align:right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php
                                $defaultUnitPrice = old("items.{$product->id}.unit_price", (float) $product->unit_price);
                                $defaultQuantity = old("items.{$product->id}.quantity", 0);
                                $defaultNotes = old("items.{$product->id}.notes", '');
                            @endphp
                            <tr data-sale-line>
                                <td class="sale-code-cell">
                                    <strong>{{ $product->code }}</strong>
                                </td>
                                <td class="sale-product-cell">
                                    <strong class="sale-product-name">{{ $product->name }}</strong>
                                    <span class="sale-product-unit">{{ $product->unit }}</span>
                                </td>
                                <td>
                                    <span class="sale-category-pill">{{ $product->category_initials }}</span>
                                </td>
                                <td>
                                    <span class="sale-state-pill {{ $product->machine_stock_class }}">
                                        {{ $product->machine_stock_label }}
                                    </span>
                                </td>
                                <td class="sale-cost-cell">
                                    ${{ number_format((float) $product->cost, 0, ',', '.') }}
                                </td>
                                <td class="sale-stock-cell">
                                    <span class="sale-stock-count">{{ number_format((int) $product->machine_stock, 0, ',', '.') }}</span>
                                </td>
                                <td class="sale-price-cell">
                                    <input type="number"
                                           name="items[{{ $product->id }}][unit_price]"
                                           value="{{ $defaultUnitPrice }}"
                                           min="0"
                                           step="1"
                                           class="form-input sale-line-input sale-line-price"
                                           inputmode="numeric"
                                           data-line-price>
                                </td>
                                <td class="sale-notes-cell">
                                    <input type="text"
                                           name="items[{{ $product->id }}][notes]"
                                           value="{{ $defaultNotes }}"
                                           class="form-input sale-line-input"
                                           maxlength="120"
                                           placeholder="Promo, ajuste o comentario">
                                </td>
                                <td class="sale-qty-cell">
                                    <input type="number"
                                           name="items[{{ $product->id }}][quantity]"
                                           value="{{ $defaultQuantity }}"
                                           min="0"
                                           step="1"
                                           class="form-input sale-line-input sale-line-qty"
                                           inputmode="numeric"
                                           data-line-qty>
                                </td>
                                <td class="sale-subtotal-cell">
                                    <span data-line-subtotal>$0</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sales-total-strip">
                <div>
                    <span class="sales-total-strip__label">Total estimado</span>
                    <strong class="sales-total-strip__value" data-sales-total>$0</strong>
                </div>
                <p class="sales-total-strip__help">
                    El costo es referencial, el precio unitario puedes ajustarlo por máquina o ubicación.
                </p>
            </div>

            <div class="form-group">
                <label class="form-label">Observaciones generales</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Notas opcionales sobre la venta...">{{ old('notes') }}</textarea>
            </div>

            <div style="display:flex;gap:var(--space-3);padding-top:var(--space-4);border-top:1px solid var(--gacov-border);flex-wrap:wrap">
                <button type="submit" class="btn btn-primary" style="width:auto" @disabled(! $selectedMachine)>
                    {{ $selectedMachine ? 'Registrar venta' : 'Selecciona una máquina primero' }}
                </button>
                <a href="{{ route('driver.dashboard', $routeQuery) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .sale-machine-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: var(--space-3);
        margin-top: var(--space-4);
    }

    .sale-machine-summary__card {
        display: grid;
        gap: 4px;
        padding: var(--space-4);
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(248,250,252,.92));
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .sale-machine-summary__eyebrow {
        font-size: 10px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--gacov-text-muted);
    }

    .sale-machine-summary__card strong {
        font-size: 16px;
        color: var(--gacov-text-primary);
    }

    .sale-machine-summary__card span:last-child {
        font-size: 12px;
        color: var(--gacov-text-secondary);
    }

    .sale-category-pill,
    .sale-state-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .sale-category-pill {
        background: rgba(17, 24, 39, 0.08);
        color: var(--gacov-text-primary);
    }

    .sale-state-pill--ok {
        background: rgba(16, 185, 129, 0.12);
        color: #047857;
    }

    .sale-state-pill--low {
        background: rgba(245, 158, 11, 0.14);
        color: #b45309;
    }

    .sale-state-pill--empty {
        background: rgba(239, 68, 68, 0.14);
        color: #b91c1c;
    }

    .sale-stock-count {
        display: inline-flex;
        min-width: 52px;
        justify-content: flex-end;
        font-weight: 700;
        color: var(--gacov-text-primary);
    }

    .sales-line-items-table {
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .sales-line-items-table thead th {
        background: transparent;
        border-bottom: 0;
        color: var(--gacov-text-muted);
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
        padding-bottom: 10px;
    }

    .sales-line-items-table tbody tr {
        background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.96));
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .sales-line-items-table tbody td {
        padding: 14px 12px;
        vertical-align: middle;
        border-top: 1px solid rgba(148, 163, 184, 0.16);
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        color: var(--gacov-text-primary);
    }

    .sales-line-items-table tbody td:first-child {
        border-left: 1px solid rgba(148, 163, 184, 0.16);
        border-top-left-radius: 18px;
        border-bottom-left-radius: 18px;
    }

    .sales-line-items-table tbody td:last-child {
        border-right: 1px solid rgba(148, 163, 184, 0.16);
        border-top-right-radius: 18px;
        border-bottom-right-radius: 18px;
    }

    .sale-code-cell {
        white-space: nowrap;
        font-weight: 900;
    }

    .sale-product-cell {
        min-width: 220px;
    }

    .sale-product-name {
        display: block;
        font-size: 14px;
        font-weight: 900;
        line-height: 1.15;
    }

    .sale-product-unit {
        display: block;
        font-size: 12px;
        margin-top: 4px;
        color: var(--gacov-text-secondary);
    }

    .sale-cost-cell {
        text-align: right;
        color: #dc2626;
        font-weight: 800;
        white-space: nowrap;
    }

    .sale-stock-cell {
        text-align: right;
    }

    .sale-price-cell,
    .sale-notes-cell,
    .sale-qty-cell {
        min-width: 120px;
    }

    .sale-subtotal-cell {
        text-align: right;
        font-weight: 900;
        color: var(--gacov-primary);
        white-space: nowrap;
    }

    .sales-total-strip {
        display: flex;
        justify-content: space-between;
        gap: var(--space-4);
        align-items: flex-end;
        margin: var(--space-4) 0;
        padding: var(--space-4);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(215, 25, 32, 0.06), rgba(17, 17, 17, 0.04));
        border: 1px solid rgba(148, 163, 184, 0.16);
        flex-wrap: wrap;
    }

    .sales-total-strip__label {
        display: block;
        font-size: 11px;
        color: var(--gacov-text-muted);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 4px;
    }

    .sales-total-strip__value {
        font-size: 28px;
        color: var(--gacov-text-primary);
    }

    .sales-total-strip__help {
        margin: 0;
        font-size: 12px;
        color: var(--gacov-text-secondary);
    }

    @media (max-width: 960px) {
        .sales-line-items-table {
            min-width: 1120px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    });

    const rows = Array.from(document.querySelectorAll('[data-sale-line]'));
    const totalElement = document.querySelector('[data-sales-total]');

    const recalculate = () => {
        let total = 0;

        rows.forEach((row) => {
            const priceInput = row.querySelector('[data-line-price]');
            const qtyInput = row.querySelector('[data-line-qty]');
            const subtotalElement = row.querySelector('[data-line-subtotal]');

            const price = Number(priceInput?.value || 0);
            const quantity = Number(qtyInput?.value || 0);
            const subtotal = price * quantity;

            total += subtotal;

            if (subtotalElement) {
                subtotalElement.textContent = formatter.format(subtotal);
            }
        });

        if (totalElement) {
            totalElement.textContent = formatter.format(total);
        }
    };

    rows.forEach((row) => {
        row.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', recalculate);
            input.addEventListener('change', recalculate);
        });
    });

    recalculate();
});
</script>
@endpush
