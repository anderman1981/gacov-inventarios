@extends('layouts.app')
@section('title', 'Surtir máquina')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@section('content')
@php
    $routeQuery = $route?->id ? ['route_id' => $route->id] : [];
    $selectedMachineId = (string) old('machine_id', request('machine_id', ''));
    $selectedMachine = $machines->firstWhere('id', (int) $selectedMachineId);
    $machineLocations = $machines
        ->map(fn ($machine) => filled($machine->location) ? trim((string) $machine->location) : 'Sin ubicación definida')
        ->unique(fn ($location) => mb_strtolower((string) $location))
        ->sort()
        ->values();
    $selectedLocationGroup = (string) old(
        'stocking_location_group',
        filled($selectedMachine?->location) ? trim((string) $selectedMachine->location) : ''
    );
@endphp
<div class="page-header">
    <h1 class="page-title">Surtir máquina</h1>
    <p class="page-subtitle"><a href="{{ route('driver.dashboard', $routeQuery) }}" style="color:var(--gacov-text-muted);text-decoration:none">Mi ruta</a> / Surtir máquina</p>
</div>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:var(--space-5)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div>
        <strong>No pudimos registrar el surtido.</strong>
        <ul style="margin-top:6px;padding-left:18px;list-style:disc;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

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

<div class="panel" style="max-width:900px">
    <div class="panel-header">
        <span class="panel-title">Registro de surtido</span>
        @if($vehicleWarehouse)
        <span class="badge badge-info">Vehículo: {{ $vehicleWarehouse->name }}</span>
        @endif
    </div>
    <div class="panel-body">
        <div class="alert alert-info" style="margin-bottom:var(--space-5)">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-3a1 1 0 10-2 0 1 1 0 002 0zm-2 3a1 1 0 000 2v2a1 1 0 102 0v-2a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
            <div>
                <strong>Surtido manual para conductor.</strong>
                Registra aquí las cantidades que vas a cargar en la máquina seleccionada.
            </div>
        </div>

        <form method="POST" action="{{ route('driver.stocking.store') }}">
            @csrf
            <input type="hidden" name="route_id" value="{{ $route?->id }}">
            <div class="form-group" style="margin-bottom:var(--space-6)">
                <label class="form-label" for="stocking-location-group">Ubicación de la máquina <span style="color:var(--gacov-error)">*</span></label>
                <select id="stocking-location-group" name="stocking_location_group" class="form-input" {{ $machines->isEmpty() ? 'disabled' : '' }}>
                    <option value="">Seleccionar ubicación...</option>
                    @foreach($machineLocations as $locationGroup)
                    @php
                        $locationMachineCount = $machines->filter(function ($machine) use ($locationGroup) {
                            $machineLocation = filled($machine->location) ? trim((string) $machine->location) : 'Sin ubicación definida';

                            return mb_strtolower($machineLocation) === mb_strtolower((string) $locationGroup);
                        })->count();
                    @endphp
                    <option value="{{ $locationGroup }}" {{ $selectedLocationGroup === (string) $locationGroup ? 'selected' : '' }}>
                        {{ $locationGroup }} ({{ $locationMachineCount }} máquina{{ $locationMachineCount === 1 ? '' : 's' }})
                    </option>
                    @endforeach
                </select>
                <p style="font-size:12px;color:var(--gacov-text-muted);margin-top:6px">
                    Primero elige la ubicación y luego el sistema te mostrará solo las máquinas de ese punto.
                </p>
            </div>

            <div class="form-group" style="margin-bottom:var(--space-6)">
                <label class="form-label">Máquina a surtir <span style="color:var(--gacov-error)">*</span></label>
                <select
                    id="stocking-machine-id"
                    name="machine_id"
                    class="form-input {{ $errors->has('machine_id') ? 'is-invalid' : '' }}"
                    data-placeholder-default="Seleccionar ubicación primero..."
                    data-placeholder-ready="Seleccionar máquina..."
                    required
                    {{ $machines->isEmpty() ? 'disabled' : '' }}>
                    <option value="">Seleccionar máquina...</option>
                    @foreach($machines as $machine)
                    @php
                        $machineCode = strtoupper(trim((string) $machine->code));
                        $machineSelected = old('machine_id') == $machine->id || request('machine_id') == $machine->id;
                        $machineLocationGroup = filled($machine->location) ? trim((string) $machine->location) : 'Sin ubicación definida';
                    @endphp
                    <option
                        value="{{ $machine->id }}"
                        data-machine-option="true"
                        data-machine-code="{{ $machineCode }}"
                        data-machine-name="{{ $machine->name }}"
                        data-location-group="{{ $machineLocationGroup }}"
                        {{ $machineSelected ? 'selected' : '' }}>
                        {{ $machine->code }} — {{ $machine->name }}{{ $machine->location ? ' (' . $machineLocationGroup . ')' : '' }}
                    </option>
                    @endforeach
                </select>
                <p style="font-size:12px;color:var(--gacov-text-muted);margin-top:6px">
                    Esto evita mezclar máquinas de diferentes puntos de la ruta y hace más rápido el registro.
                </p>
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

            {{-- Geolocalización del reporte --}}
            <div class="form-group" style="background:var(--gacov-bg-elevated);padding:var(--space-4);border-radius:var(--radius-md);margin-bottom:var(--space-4)">
                <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-2)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" style="color:var(--gacov-primary)"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    <span class="form-label" style="margin-bottom:0">Ubicación GPS</span>
                    <span id="geolocation-status" class="badge badge-neutral" style="font-size:11px">Obteniendo...</span>
                </div>
                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                <input type="hidden" name="geolocation_accuracy" id="geolocation_accuracy" value="{{ old('geolocation_accuracy') }}">
                <p id="geolocation-info" style="font-size:12px;color:var(--gacov-text-muted);margin:0">
                    Cargando geolocalización...
                </p>
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
    const locationSelect = document.getElementById('stocking-location-group');
    const machineSelect = document.getElementById('stocking-machine-id');
    const productRows = Array.from(document.querySelectorAll('.stocking-product-row'));
    const stockingForm = machineSelect?.closest('form');
    let importedRows = [];
    const machineOptions = machineSelect
        ? Array.from(machineSelect.querySelectorAll('option[data-machine-option="true"]')).map((option) => ({
            value: option.value,
            label: option.textContent?.trim() ?? '',
            locationGroup: option.dataset.locationGroup ?? '',
            machineCode: option.dataset.machineCode ?? '',
            machineName: option.dataset.machineName ?? '',
        }))
        : [];

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

    function createMachineOption(optionConfig) {
        const option = new Option(optionConfig.label, optionConfig.value, false, false);
        option.dataset.machineOption = 'true';
        option.dataset.locationGroup = optionConfig.locationGroup;
        option.dataset.machineCode = optionConfig.machineCode;
        option.dataset.machineName = optionConfig.machineName;

        return option;
    }

    function refreshMachineOptions() {
        if (!(locationSelect instanceof HTMLSelectElement) || !(machineSelect instanceof HTMLSelectElement)) {
            return;
        }

        const selectedLocation = locationSelect.value;
        const previousMachineId = machineSelect.value;
        const filteredMachines = selectedLocation === ''
            ? []
            : machineOptions.filter((option) => option.locationGroup === selectedLocation);

        const placeholderLabel = selectedLocation === ''
            ? (machineSelect.dataset.placeholderDefault ?? 'Seleccionar ubicación primero...')
            : (filteredMachines.length > 0
                ? (machineSelect.dataset.placeholderReady ?? 'Seleccionar máquina...')
                : 'No hay máquinas activas en esta ubicación');

        machineSelect.innerHTML = '';
        machineSelect.appendChild(new Option(placeholderLabel, ''));

        filteredMachines.forEach((optionConfig) => {
            machineSelect.appendChild(createMachineOption(optionConfig));
        });

        const nextMachineId = filteredMachines.some((option) => option.value === previousMachineId)
            ? previousMachineId
            : (filteredMachines.length === 1 ? filteredMachines[0].value : '');

        machineSelect.disabled = selectedLocation === '' || filteredMachines.length === 0;
        machineSelect.value = nextMachineId;
        machineSelect.dispatchEvent(new Event('change', { bubbles: true }));
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

    locationSelect?.addEventListener('change', () => {
        refreshMachineOptions();
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

    if (locationSelect instanceof HTMLSelectElement && machineSelect instanceof HTMLSelectElement) {
        if (!locationSelect.value && machineSelect.value) {
            const currentMachine = machineOptions.find((option) => option.value === machineSelect.value);

            if (currentMachine) {
                locationSelect.value = currentMachine.locationGroup;
            }
        }

        if (!locationSelect.value && machineOptions.length === 1) {
            locationSelect.value = machineOptions[0].locationGroup;
        }

        refreshMachineOptions();
    }

    // Geolocalización del reporte
    const geolocationStatus = document.getElementById('geolocation-status');
    const geolocationInfo = document.getElementById('geolocation-info');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const accuracyInput = document.getElementById('geolocation_accuracy');

    function updateGeolocationStatus(status, message) {
        if (!geolocationStatus) return;

        geolocationStatus.textContent = status;
        switch (status) {
            case 'success':
                geolocationStatus.className = 'badge badge-success';
                break;
            case 'error':
                geolocationStatus.className = 'badge badge-error';
                break;
            default:
                geolocationStatus.className = 'badge badge-neutral';
        }

        if (geolocationInfo) {
            geolocationInfo.textContent = message;
        }
    }

    function handleGeolocationError(error) {
        console.warn('Geolocation error:', error);

        switch (error.code) {
            case error.PERMISSION_DENIED:
                updateGeolocationStatus('error', 'Permiso de ubicación denegado. Permite el acceso en tu navegador para registrar la posición.');
                break;
            case error.POSITION_UNAVAILABLE:
                updateGeolocationStatus('error', 'Ubicación no disponible. El GPS del dispositivo no funciona.');
                break;
            case error.TIMEOUT:
                updateGeolocationStatus('error', 'Timeout al obtener ubicación. Intenta de nuevo.');
                break;
            default:
                updateGeolocationStatus('error', 'Error desconocido al obtener ubicación.');
        }
    }

    function handleGeolocationSuccess(position) {
        const lat = position.coords.latitude.toFixed(6);
        const lng = position.coords.longitude.toFixed(6);
        const accuracy = Math.round(position.coords.accuracy);

        if (latitudeInput) latitudeInput.value = lat;
        if (longitudeInput) longitudeInput.value = lng;
        if (accuracyInput) accuracyInput.value = accuracy + 'm';

        updateGeolocationStatus(
            'success',
            `Lat: ${lat}, Lng: ${lng} (precisión: ±${accuracy}m)`
        );
    }

    function requestGeolocation() {
        if (!navigator.geolocation) {
            updateGeolocationStatus('error', 'Geolocalización no soportada por este navegador.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            handleGeolocationSuccess,
            handleGeolocationError,
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 60000
            }
        );
    }

    // Solicitar geolocalización al cargar la página
    requestGeolocation();

    // Botón para reintentar geolocalización
    if (geolocationStatus) {
        geolocationStatus.style.cursor = 'pointer';
        geolocationStatus.title = 'Clic para reintentar';
        geolocationStatus.addEventListener('click', requestGeolocation);
    }
});
</script>
@endpush
