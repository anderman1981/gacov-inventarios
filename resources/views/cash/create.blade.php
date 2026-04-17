@extends('layouts.app')
@section('title', 'Nueva Entrega de Efectivo')

@section('content')
<div class="page-header" style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-6)">
    <a href="{{ route('cash.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">
        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
    </a>
    <div>
        <h1 class="page-title">Nueva Entrega de Efectivo</h1>
        <p class="page-subtitle">Registrar billetes y monedas entregados a un conductor</p>
    </div>
</div>

@if(session('error'))
<div class="alert alert-error" style="margin-bottom:var(--space-4)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:var(--space-4)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div>
        <strong>No se pudo guardar. Revisa los siguientes campos:</strong>
        <ul style="margin-top:6px;padding-left:18px;list-style:disc">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('cash.store') }}">
    @csrf

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">

        {{-- Datos generales --}}
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">Datos de la entrega</h3>
            </div>
            <div class="panel-body" style="display:flex;flex-direction:column;gap:var(--space-5)">

                <div class="form-group">
                    <label class="form-label">Conductor <span style="color:var(--gacov-error)">*</span></label>
                    <select name="driver_user_id" class="form-input @error('driver_user_id') is-invalid @enderror" required>
                        <option value="">— Seleccionar conductor —</option>
                        @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ old('driver_user_id') == $driver->id ? 'selected' : '' }}>
                            {{ $driver->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('driver_user_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Ruta <span style="color:var(--gacov-error)">*</span></label>
                    <select name="route_id" class="form-input @error('route_id') is-invalid @enderror" required>
                        <option value="">— Seleccionar ruta —</option>
                        @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                            {{ $route->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('route_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de entrega <span style="color:var(--gacov-error)">*</span></label>
                    <input type="date" name="delivery_date" class="form-input @error('delivery_date') is-invalid @enderror"
                           value="{{ old('delivery_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    @error('delivery_date')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Notas / Observaciones</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Observaciones opcionales...">{{ old('notes') }}</textarea>
                    @error('notes')<p class="form-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- Denominaciones --}}
        <div style="display:flex;flex-direction:column;gap:var(--space-6)">

            {{-- Billetes --}}
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="display:inline;vertical-align:-2px;margin-right:6px;color:var(--gacov-success)"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/></svg>
                        Billetes
                    </h3>
                </div>
                <div class="panel-body">
                    @php
                        $billLabels = [
                            'bill_100000' => '$100.000',
                            'bill_50000'  => '$50.000',
                            'bill_20000'  => '$20.000',
                            'bill_10000'  => '$10.000',
                            'bill_5000'   => '$5.000',
                            'bill_2000'   => '$2.000',
                            'bill_1000'   => '$1.000',
                        ];
                    @endphp
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                        @foreach($billLabels as $field => $label)
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:12px">{{ $label }}</label>
                            <div style="position:relative">
                                <input type="number" name="{{ $field }}"
                                       class="form-input denomination-input"
                                       data-denomination="{{ \App\Models\DriverCashDelivery::BILL_DENOMINATIONS[$field] }}"
                                       data-type="bills"
                                       value="{{ old($field, 0) }}"
                                       min="0" max="9999" step="1"
                                       style="padding-right:40px">
                                <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--gacov-text-muted)">uds</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div style="margin-top:var(--space-4);padding:var(--space-3);background:rgba(16,185,129,.08);border-radius:var(--radius-md);display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:13px;color:var(--gacov-text-muted)">Subtotal billetes:</span>
                        <strong style="color:var(--gacov-success);font-size:16px" id="subtotal-bills">$0</strong>
                    </div>
                </div>
            </div>

            {{-- Monedas --}}
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="display:inline;vertical-align:-2px;margin-right:6px;color:var(--gacov-warning)"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/></svg>
                        Monedas
                    </h3>
                </div>
                <div class="panel-body">
                    @php
                        $coinLabels = [
                            'coin_1000' => '$1.000',
                            'coin_500'  => '$500',
                            'coin_200'  => '$200',
                            'coin_100'  => '$100',
                            'coin_50'   => '$50',
                        ];
                    @endphp
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                        @foreach($coinLabels as $field => $label)
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:12px">{{ $label }}</label>
                            <div style="position:relative">
                                <input type="number" name="{{ $field }}"
                                       class="form-input denomination-input"
                                       data-denomination="{{ \App\Models\DriverCashDelivery::COIN_DENOMINATIONS[$field] }}"
                                       data-type="coins"
                                       value="{{ old($field, 0) }}"
                                       min="0" max="9999" step="1"
                                       style="padding-right:40px">
                                <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--gacov-text-muted)">uds</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div style="margin-top:var(--space-4);padding:var(--space-3);background:rgba(245,158,11,.08);border-radius:var(--radius-md);display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:13px;color:var(--gacov-text-muted)">Subtotal monedas:</span>
                        <strong style="color:var(--gacov-warning);font-size:16px" id="subtotal-coins">$0</strong>
                    </div>
                </div>
            </div>

        </div>{{-- fin columna derecha --}}

    </div>{{-- fin grid --}}

    {{-- Total general y submit --}}
    <div style="margin-top:var(--space-6);display:flex;justify-content:flex-end;align-items:center;gap:var(--space-6)">
        <div style="text-align:right">
            <p style="font-size:12px;color:var(--gacov-text-muted);margin-bottom:4px">TOTAL A ENTREGAR</p>
            <p style="font-size:28px;font-weight:700;color:var(--gacov-primary)" id="grand-total">$0</p>
        </div>
        <div style="display:flex;gap:var(--space-3)">
            <a href="{{ route('cash.index') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="width:auto">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                Registrar entrega
            </button>
        </div>
    </div>

</form>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const fmt = (n) => '$' + n.toLocaleString('es-CO');

    function recalculate() {
        let bills = 0;
        let coins = 0;

        document.querySelectorAll('.denomination-input').forEach(function (input) {
            const qty  = parseInt(input.value, 10) || 0;
            const denom = parseInt(input.dataset.denomination, 10) || 0;
            const sub   = qty * denom;
            if (input.dataset.type === 'bills') bills += sub;
            else coins += sub;
        });

        document.getElementById('subtotal-bills').textContent = fmt(bills);
        document.getElementById('subtotal-coins').textContent = fmt(coins);
        document.getElementById('grand-total').textContent    = fmt(bills + coins);
    }

    document.querySelectorAll('.denomination-input').forEach(function (input) {
        input.addEventListener('input', recalculate);
    });

    recalculate();
})();
</script>
@endpush
