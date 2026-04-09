@extends('layouts.app')
@section('title', 'Editar producto')

@section('content')
<div class="page-header">
    <h1 class="page-title">Editar producto</h1>
    <p class="page-subtitle"><a href="{{ route('products.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">Productos</a> / {{ $product->name }}</p>
</div>

<div class="panel" style="max-width:800px">
    <div class="panel-header">
        <span class="panel-title">Datos del producto</span>
        <div style="display:flex;gap:var(--space-3);align-items:center">
            <span class="badge badge-neutral">ID #{{ $product->id }}</span>
            <code style="font-size:12px;color:var(--gacov-primary)">{{ $product->code }}</code>
        </div>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('products.update', $product) }}">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5)">
                <div class="form-group">
                    <label class="form-label">Nombre <span style="color:var(--gacov-error)">*</span></label>
                    <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name', $product->name) }}" required>
                    @error('name')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Código / SKU <span style="color:var(--gacov-error)">*</span></label>
                    <input type="text" name="code" class="form-input {{ $errors->has('code') ? 'is-invalid' : '' }}"
                           value="{{ old('code', $product->code) }}" required>
                    @error('code')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Código WorldOffice</label>
                    <input type="text" name="worldoffice_code" class="form-input {{ $errors->has('worldoffice_code') ? 'is-invalid' : '' }}"
                           value="{{ old('worldoffice_code', $product->worldoffice_code) }}">
                    @error('worldoffice_code')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría <span style="color:var(--gacov-error)">*</span></label>
                    <select name="category" class="form-input" required>
                        <option value="">Seleccionar...</option>
                        <option value="snack" {{ old('category', $product->category) === 'snack' ? 'selected' : '' }}>Snacks</option>
                        <option value="bebida_fria" {{ old('category', $product->category) === 'bebida_fria' ? 'selected' : '' }}>Bebidas frías</option>
                        <option value="bebida_caliente" {{ old('category', $product->category) === 'bebida_caliente' ? 'selected' : '' }}>Bebidas calientes</option>
                        <option value="insumo" {{ old('category', $product->category) === 'insumo' ? 'selected' : '' }}>Insumos</option>
                        <option value="otro" {{ old('category', $product->category) === 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                    @error('category')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Unidad de medida <span style="color:var(--gacov-error)">*</span></label>
                    <select name="unit_of_measure" class="form-input" required>
                        @foreach(['Und.','Kg','Lt','Caja','Paquete','Bolsa'] as $u)
                        <option value="{{ $u }}" {{ old('unit_of_measure', $product->unit_of_measure) === $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                    @error('unit_of_measure')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Precio de venta (COP) <span style="color:var(--gacov-error)">*</span></label>
                    <input type="number" name="unit_price" class="form-input"
                           value="{{ old('unit_price', $product->unit_price) }}" min="0" step="50" required>
                    @error('unit_price')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Alerta mínima de stock</label>
                    <input type="number" name="min_stock_alert" class="form-input"
                           value="{{ old('min_stock_alert', $product->min_stock_alert) }}" min="0" step="1">
                    @error('min_stock_alert')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:var(--space-3);padding-top:var(--space-6)">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $product->is_active ? '1' : '0') === '1' ? 'checked' : '' }}
                           style="width:18px;height:18px;accent-color:var(--gacov-primary)">
                    <label for="is_active" class="form-label" style="margin-bottom:0;cursor:pointer">Producto activo</label>
                </div>
            </div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gacov-border)">
                <button type="submit" class="btn btn-primary" style="width:auto">Guardar cambios</button>
                <a href="{{ route('products.index') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
