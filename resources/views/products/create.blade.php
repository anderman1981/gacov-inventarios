@extends('layouts.app')
@section('title', 'Nuevo producto')

@section('content')
<div class="page-header">
    <h1 class="page-title">Nuevo producto</h1>
    <p class="page-subtitle"><a href="{{ route('products.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">Productos</a> / Nuevo</p>
</div>

<div class="panel" style="max-width:800px">
    <div class="panel-header"><span class="panel-title">Datos del producto</span></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5)">
                <div class="form-group">
                    <label class="form-label">Nombre <span style="color:var(--gacov-error)">*</span></label>
                    <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name') }}" required>
                    @error('name')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Código / SKU <span style="color:var(--gacov-error)">*</span></label>
                    <input type="text" name="code" class="form-input {{ $errors->has('code') ? 'is-invalid' : '' }}"
                           value="{{ old('code') }}" placeholder="Ej: 124" required>
                    @error('code')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Código WorldOffice</label>
                    <input type="text" name="worldoffice_code" class="form-input {{ $errors->has('worldoffice_code') ? 'is-invalid' : '' }}"
                           value="{{ old('worldoffice_code') }}" placeholder="Ej: 124">
                    @error('worldoffice_code')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría <span style="color:var(--gacov-error)">*</span></label>
                    <select name="category" class="form-input {{ $errors->has('category') ? 'is-invalid' : '' }}" required>
                        <option value="">Seleccionar...</option>
                        <option value="snack" {{ old('category') === 'snack' ? 'selected' : '' }}>Snacks</option>
                        <option value="bebida_fria" {{ old('category') === 'bebida_fria' ? 'selected' : '' }}>Bebidas frías</option>
                        <option value="bebida_caliente" {{ old('category') === 'bebida_caliente' ? 'selected' : '' }}>Bebidas calientes</option>
                        <option value="insumo" {{ old('category') === 'insumo' ? 'selected' : '' }}>Insumos</option>
                        <option value="otro" {{ old('category') === 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                    @error('category')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Unidad de medida <span style="color:var(--gacov-error)">*</span></label>
                    <select name="unit_of_measure" class="form-input {{ $errors->has('unit_of_measure') ? 'is-invalid' : '' }}" required>
                        <option value="">Seleccionar...</option>
                        @foreach(['Und.','Kg','Lt','Caja','Paquete','Bolsa'] as $u)
                        <option value="{{ $u }}" {{ old('unit_of_measure') === $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                    @error('unit_of_measure')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Costo (COP)</label>
                    <input type="number" name="cost" class="form-input {{ $errors->has('cost') ? 'is-invalid' : '' }}"
                           value="{{ old('cost', 0) }}" min="0" step="50">
                    @error('cost')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Precio de venta mínimo (COP)</label>
                    <input type="number" name="min_sale_price" class="form-input {{ $errors->has('min_sale_price') ? 'is-invalid' : '' }}"
                           value="{{ old('min_sale_price', 0) }}" min="0" step="50">
                    @error('min_sale_price')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Precio de venta (COP) <span style="color:var(--gacov-error)">*</span></label>
                    <input type="number" name="unit_price" class="form-input {{ $errors->has('unit_price') ? 'is-invalid' : '' }}"
                           value="{{ old('unit_price') }}" min="0" step="50" required>
                    @error('unit_price')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Alerta mínima de stock</label>
                    <input type="number" name="min_stock_alert" class="form-input {{ $errors->has('min_stock_alert') ? 'is-invalid' : '' }}"
                           value="{{ old('min_stock_alert', 0) }}" min="0" step="1">
                    @error('min_stock_alert')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Proveedor</label>
                    <input type="text" name="supplier" class="form-input {{ $errors->has('supplier') ? 'is-invalid' : '' }}"
                           value="{{ old('supplier') }}" placeholder="Ej: Distribuidora X">
                    @error('supplier')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">SKU / Proveedor</label>
                    <input type="text" name="supplier_sku" class="form-input {{ $errors->has('supplier_sku') ? 'is-invalid' : '' }}"
                           value="{{ old('supplier_sku') }}" placeholder="Código del proveedor">
                    @error('supplier_sku')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de compra</label>
                    <input type="date" name="purchase_date" class="form-input {{ $errors->has('purchase_date') ? 'is-invalid' : '' }}"
                           value="{{ old('purchase_date') }}">
                    @error('purchase_date')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de vencimiento</label>
                    <input type="date" name="expiration_date" class="form-input {{ $errors->has('expiration_date') ? 'is-invalid' : '' }}"
                           value="{{ old('expiration_date') }}">
                    @error('expiration_date')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:var(--space-3);padding-top:var(--space-6)">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                           style="width:18px;height:18px;accent-color:var(--gacov-primary)">
                    <label for="is_active" class="form-label" style="margin-bottom:0;cursor:pointer">Producto activo</label>
                </div>
            </div>
            <div class="panel" style="margin-top:var(--space-5);padding:var(--space-4);background:var(--gacov-bg-elevated)">
                <div class="panel-header" style="margin:0 0 var(--space-3) 0">
                    <span class="panel-title">Metadatos</span>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Fecha de creación</label>
                    <input type="text" class="form-input" value="Se registra automáticamente al guardar el producto" disabled>
                </div>
            </div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gacov-border)">
                <button type="submit" class="btn btn-primary" style="width:auto">Guardar producto</button>
                <a href="{{ route('products.index') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
