@extends('layouts.app')
@section('title', 'Registrar venta')

@section('content')
@php($routeQuery = $route?->id ? ['route_id' => $route->id] : [])
<div class="page-header">
    <h1 class="page-title">Registrar venta</h1>
    <p class="page-subtitle"><a href="{{ route('driver.dashboard', $routeQuery) }}" style="color:var(--gacov-text-muted);text-decoration:none">Mi ruta</a> / Registrar venta</p>
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

@if($availableRoutes->isNotEmpty())
<div class="panel" style="margin-bottom:var(--space-5)">
    <div class="panel-body">
        <form method="GET" action="{{ route('driver.sales.create') }}" class="driver-route-switcher">
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
            @if(request('machine_id'))
            <input type="hidden" name="machine_id" value="{{ request('machine_id') }}">
            @endif
            <noscript><button type="submit" class="btn btn-primary" style="width:auto">Cargar ruta</button></noscript>
        </form>
    </div>
</div>
@endif

<div class="panel" style="max-width:900px">
    <div class="panel-header">
        <span class="panel-title">Registro de venta en máquina</span>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('driver.sales.store') }}">
            @csrf
            <input type="hidden" name="route_id" value="{{ $route?->id }}">
            <div class="form-group" style="margin-bottom:var(--space-6)">
                <label class="form-label">Máquina <span style="color:var(--gacov-error)">*</span></label>
                <select name="machine_id" class="form-input {{ $errors->has('machine_id') ? 'is-invalid' : '' }}" required>
                    <option value="">Seleccionar máquina...</option>
                    @foreach($machines as $machine)
                    <option value="{{ $machine->id }}" {{ (old('machine_id') == $machine->id || request('machine_id') == $machine->id) ? 'selected' : '' }}>
                        {{ $machine->code }} — {{ $machine->name }}
                        @if($machine->location) ({{ $machine->location }}) @endif
                    </option>
                    @endforeach
                </select>
                @error('machine_id')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div style="margin-bottom:var(--space-6)">
                <p class="form-label" style="margin-bottom:var(--space-3)">Productos vendidos</p>
                <p style="font-size:12px;color:var(--gacov-text-muted);margin-bottom:var(--space-4)">
                    Ingresa la cantidad vendida de cada producto. El precio se toma del catálogo.
                </p>
                <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align:right">Precio unit. (COP)</th>
                            <th style="text-align:center;width:140px">Cantidad vendida</th>
                            <th style="text-align:right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr>
                            <td><strong>{{ $product->name }}</strong></td>
                            <td style="text-align:right">
                                <input type="hidden" name="items[{{ $product->id }}][unit_price]" value="{{ $product->unit_price }}">
                                ${{ number_format((float)$product->unit_price, 0, ',', '.') }}
                            </td>
                            <td>
                                <input type="number"
                                       name="items[{{ $product->id }}][quantity]"
                                       value="{{ old("items.{$product->id}.quantity", 0) }}"
                                       min="0"
                                       step="1"
                                       class="form-input"
                                       style="text-align:center;padding:6px 8px">
                            </td>
                            <td style="text-align:right;color:var(--gacov-text-muted);font-size:13px">
                                —
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Notas opcionales sobre la venta...">{{ old('notes') }}</textarea>
            </div>

            <div style="display:flex;gap:var(--space-3);padding-top:var(--space-4);border-top:1px solid var(--gacov-border)">
                <button type="submit" class="btn btn-primary" style="width:auto">
                    Registrar venta
                </button>
                <a href="{{ route('driver.dashboard', $routeQuery) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
