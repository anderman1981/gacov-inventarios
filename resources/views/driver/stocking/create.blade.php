@extends('layouts.app')
@section('title', 'Surtir máquina')

@section('content')
@php
    $routeQuery = $route?->id ? ['route_id' => $route->id] : [];
@endphp
<div class="page-header">
    <h1 class="page-title">Surtir máquina</h1>
    <p class="page-subtitle"><a href="{{ route('driver.dashboard', $routeQuery) }}" style="color:var(--gacov-text-muted);text-decoration:none">Mi ruta</a> / Surtir máquina</p>
</div>

@if($availableRoutes->isNotEmpty())
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body">
        <form method="GET" action="{{ route('driver.stocking.create') }}" class="driver-route-switcher">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="driver-stocking-route-id">Seleccionar ruta</label>
                <select id="driver-stocking-route-id" name="route_id" class="form-input" onchange="this.form.submit()">
                    <option value="">Seleccionar ruta...</option>
                    @foreach($availableRoutes as $availableRoute)
                    <option value="{{ $availableRoute->id }}" {{ $route?->id === $availableRoute->id ? 'selected' : '' }}>
                        {{ $availableRoute->code }} — {{ $availableRoute->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @if(request('machine_id'))
            <input type="hidden" name="machine_id" value="{{ request('machine_id') }}">
            @endif
            <noscript><button type="submit" class="btn btn-primary" style="width:auto">Cargar ruta</button></noscript>
        </form>
    </div>
</div>
@endif

<div style="display:flex;gap:var(--space-3);flex-wrap:wrap;margin-bottom:var(--space-5)">
    <span class="badge badge-info">Ruta actual: {{ $route?->name ?? 'Sin ruta asignada' }}</span>
    @if($route?->code)
    <span class="badge badge-info">Código: {{ $route->code }}</span>
    @endif
    <span class="badge {{ $machines->isNotEmpty() ? 'badge-success' : 'badge-neutral' }}">
        Máquinas activas: {{ $machines->count() }}
    </span>
</div>

@if(!$vehicleWarehouse)
<div class="alert alert-error" style="margin-bottom:var(--space-6)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    Tu ruta no tiene bodega de vehículo configurada. Contacta al administrador.
</div>
@endif

@if($route && $machines->isNotEmpty())
<livewire:driver.stocking-photo-import :route-id="$route?->id" />
@endif

<div class="panel" style="max-width:900px">
    <div class="panel-header">
        <span class="panel-title">Registro de surtido</span>
        @if($vehicleWarehouse)
        <span class="badge badge-info">Vehículo: {{ $vehicleWarehouse->name }}</span>
        @endif
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('driver.stocking.store') }}">
            @csrf
            <input type="hidden" name="route_id" value="{{ $route?->id }}">
            <div class="form-group" style="margin-bottom:var(--space-6)">
                <label class="form-label">Máquina a surtir <span style="color:var(--gacov-error)">*</span></label>
                <select id="stocking-machine-id" name="machine_id" class="form-input {{ $errors->has('machine_id') ? 'is-invalid' : '' }}" required>
                    <option value="">Seleccionar máquina...</option>
                    @foreach($machines as $machine)
                    @php
                        $machineCode = strtoupper(trim((string) $machine->code));
                        $machineSelected = old('machine_id') == $machine->id || request('machine_id') == $machine->id;
                    @endphp
                    <option
                        value="{{ $machine->id }}"
                        data-machine-code="{{ $machineCode }}"
                        data-machine-name="{{ $machine->name }}"
                        {{ $machineSelected ? 'selected' : '' }}>
                        {{ $machine->code }} — {{ $machine->name }}{{ $machine->location ? ' (' . $machine->location . ')' : '' }}
                    </option>
                    @endforeach
                </select>
                @error('machine_id')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div style="margin-bottom:var(--space-6)">
                <p class="form-label" style="margin-bottom:var(--space-3)">Productos a surtir</p>
                <p style="font-size:12px;color:var(--gacov-text-muted);margin-bottom:var(--space-4)">
                    Ingresa solo las cantidades que vas a surtir. Deja en 0 los que no va a cargar.
                </p>
                <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Unidad</th>
                            <th style="text-align:center">Disponible en vehículo</th>
                            <th style="text-align:center;width:140px">Cantidad a surtir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        @php
                            $productCode = strtoupper(trim((string) $product->code));
                        @endphp
                        <tr class="stocking-product-row" data-code="{{ $productCode }}">
                            <td>
                                <strong>{{ $product->name }}</strong>
                                <div style="font-size:11px;color:var(--gacov-text-muted);margin-top:2px">{{ $productCode }}</div>
                            </td>
                            <td style="color:var(--gacov-text-muted)">{{ $product->unit }}</td>
                            <td style="text-align:center">
                                <span style="font-weight:600;color:{{ $product->vehicle_stock < 5 ? 'var(--gacov-error)' : ($product->vehicle_stock < 15 ? 'var(--gacov-warning)' : 'var(--gacov-success)') }}">
                                    {{ number_format((float) $product->vehicle_stock, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                <input type="number"
                                       name="items[{{ $product->id }}][quantity]"
                                       value="{{ old("items.{$product->id}.quantity", 0) }}"
                                       min="0"
                                       step="1"
                                       data-product-name="{{ $product->name }}"
                                       data-available-stock="{{ (int) $product->vehicle_stock }}"
                                       data-quantity-input="true"
                                       class="form-input"
                                       style="text-align:center;padding:6px 8px">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Notas opcionales sobre el surtido...">{{ old('notes') }}</textarea>
            </div>

            <div style="display:flex;gap:var(--space-3);padding-top:var(--space-4);border-top:1px solid var(--gacov-border)">
                <button type="submit" class="btn btn-primary" style="width:auto" {{ !$vehicleWarehouse ? 'disabled' : '' }}>
                    Registrar surtido
                </button>
                <a href="{{ route('driver.dashboard', $routeQuery) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const machineSelect = document.getElementById('stocking-machine-id');
    const productRows = Array.from(document.querySelectorAll('.stocking-product-row'));
    const stockingForm = machineSelect?.closest('form');
    let importedRows = [];

    function normalizeCode(code) {
        return String(code ?? '')
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');
    }

    function normalizeProductCode(code) {
        const normalized = normalizeCode(code);
        const digitsOnly = normalized.match(/^\d+$/);

        if (!digitsOnly) {
            return normalized;
        }

        return normalized.replace(/^0+/, '') || '0';
    }

    function normalizeMachineCode(code) {
        const normalized = normalizeCode(code);
        const digitsMatch = normalized.match(/\d+/);

        if (!digitsMatch) {
            return normalized;
        }

        return digitsMatch[0].replace(/^0+/, '') || '0';
    }

    function getSelectedMachineCode() {
        const selectedOption = machineSelect?.selectedOptions?.[0];

        return normalizeMachineCode(selectedOption?.dataset.machineCode ?? '');
    }

    function buildProductMap() {
        const rowMap = new Map();

        productRows.forEach((row) => {
            rowMap.set(normalizeProductCode(row.dataset.code ?? ''), row);
        });

        return rowMap;
    }

    function clearImportedFeedback() {
        document.getElementById('driver-stocking-photo-feedback')?.remove();
    }

    function renderFeedback(message, type = 'success') {
        clearImportedFeedback();

        const feedback = document.createElement('div');
        feedback.id = 'driver-stocking-photo-feedback';
        feedback.className = type === 'success' ? 'alert alert-success' : 'alert alert-error';
        feedback.style.marginBottom = 'var(--space-5)';
        feedback.innerHTML = `<div><strong>${message}</strong></div>`;

        const target = document.querySelector('.page-header');
        target?.insertAdjacentElement('afterend', feedback);
    }

    function validateQuantityInput(input) {
        if (!(input instanceof HTMLInputElement)) {
            return true;
        }

        const availableStock = Number(input.dataset.availableStock ?? 0);
        const requestedQuantity = Number(input.value || 0);
        const productName = input.dataset.productName ?? 'este producto';
        const tableRow = input.closest('.stocking-product-row');

        if (requestedQuantity > availableStock) {
            input.setCustomValidity(`No puedes surtir más de ${availableStock} unidad(es) para ${productName}.`);
            tableRow?.classList.add('stocking-product-row--invalid');
            return false;
        }

        input.setCustomValidity('');
        tableRow?.classList.remove('stocking-product-row--invalid');
        return true;
    }

    function refreshQuantityValidation() {
        let invalidCount = 0;

        productRows.forEach((tableRow) => {
            const input = tableRow.querySelector('[data-quantity-input="true"]');

            if (!input) {
                return;
            }

            if (!validateQuantityInput(input)) {
                invalidCount += 1;
            }
        });

        return invalidCount;
    }

    function applyImportedRowsToSelectedMachine() {
        const selectedMachineCode = getSelectedMachineCode();

        if (selectedMachineCode === '' || importedRows.length === 0) {
            return;
        }

        const productMap = buildProductMap();
        let appliedCount = 0;
        let machineColumnFound = false;

        productMap.forEach((tableRow) => {
            const input = tableRow.querySelector('[data-quantity-input="true"]');

            if (!input) {
                return;
            }

            input.value = '0';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        importedRows.forEach((row) => {
            const quantity = Number(row.quantities?.[selectedMachineCode] ?? 0);

            if (Object.prototype.hasOwnProperty.call(row.quantities ?? {}, selectedMachineCode)) {
                machineColumnFound = true;
            }

            const tableRow = productMap.get(normalizeProductCode(row.catalogCode ?? row.code ?? ''));

            if (!tableRow) {
                return;
            }

            const input = tableRow.querySelector('[data-quantity-input="true"]');

            if (!input) {
                return;
            }

            input.value = String(Math.max(0, quantity));
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            validateQuantityInput(input);

            if (quantity > 0) {
                appliedCount += 1;
            }
        });

        if (!machineColumnFound) {
            const selectedOption = machineSelect.selectedOptions?.[0];
            renderFeedback(`La planilla no trae cantidades para la máquina ${selectedOption?.textContent?.trim() ?? selectedMachineCode}.`, 'error');
            return;
        }

        const invalidCount = refreshQuantityValidation();

        if (invalidCount > 0) {
            renderFeedback(`Se cargaron cantidades para la máquina seleccionada, pero ${invalidCount} producto(s) superan el disponible en el vehículo. Ajusta esos valores antes de registrar.`, 'error');
            return;
        }

        renderFeedback(`Se aplicaron ${appliedCount} producto(s) a la máquina seleccionada desde la planilla de la ruta.`);
    }

    machineSelect?.addEventListener('change', () => {
        applyImportedRowsToSelectedMachine();
    });

    productRows.forEach((tableRow) => {
        const input = tableRow.querySelector('[data-quantity-input="true"]');

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        input.addEventListener('input', () => {
            validateQuantityInput(input);
        });

        input.addEventListener('change', () => {
            validateQuantityInput(input);
        });
    });

    stockingForm?.addEventListener('submit', (event) => {
        const invalidCount = refreshQuantityValidation();

        if (invalidCount === 0) {
            return;
        }

        event.preventDefault();
        renderFeedback(`No se pudo registrar el surtido porque ${invalidCount} producto(s) tienen cantidades mayores al disponible en el vehículo.`, 'error');

        const firstInvalidInput = stockingForm.querySelector('.stocking-product-row--invalid [data-quantity-input="true"]');

        if (firstInvalidInput instanceof HTMLInputElement) {
            firstInvalidInput.reportValidity();
            firstInvalidInput.focus();
        }
    });

    window.addEventListener('driver-stocking-photo-imported', (event) => {
        importedRows = Array.isArray(event.detail?.rows) ? event.detail.rows : [];

        applyImportedRowsToSelectedMachine();
    });
});
</script>
@endpush
