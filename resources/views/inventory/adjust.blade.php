@extends('layouts.app')
@section('title', 'Ajuste de stock')

@section('content')
<div class="page-header">
    <h1 class="page-title">Ajuste manual de stock</h1>
    <p class="page-subtitle">
        <a href="{{ route('inventory.warehouse') }}" style="color:var(--gacov-text-muted);text-decoration:none">Bodega Principal</a> / Ajuste
    </p>
</div>

<div class="panel" style="max-width:600px">
    <div class="panel-header"><span class="panel-title">Nuevo ajuste</span></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('inventory.adjust.store') }}">
            @csrf
            <input type="hidden" name="warehouse_id" value="{{ $mainWarehouse->id }}">

            <div class="form-group">
                <label class="form-label">Producto <span style="color:var(--gacov-error)">*</span></label>
                <select name="product_id" class="form-input {{ $errors->has('product_id') ? 'is-invalid' : '' }}" required>
                    <option value="">Seleccionar producto...</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}"
                            data-price="{{ $product->unit_price }}"
                            {{ (old('product_id', $selectedProduct?->id) == $product->id) ? 'selected' : '' }}>
                        {{ $product->sku }} — {{ $product->name }}
                    </option>
                    @endforeach
                </select>
                @error('product_id')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            @if($selectedProduct)
            <div style="background:var(--gacov-bg-elevated);border-radius:var(--radius-md);padding:var(--space-4);margin-bottom:var(--space-5)">
                <p style="font-size:13px;color:var(--gacov-text-muted);margin-bottom:4px">Stock actual en bodega principal</p>
                <p style="font-size:28px;font-weight:700;color:{{ $currentStock < 10 ? 'var(--gacov-error)' : 'var(--gacov-success)' }}">
                    {{ number_format((float) $currentStock, 0, ',', '.') }} <span style="font-size:14px;font-weight:400;color:var(--gacov-text-muted)">{{ $selectedProduct->unit }}</span>
                </p>
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">Nueva cantidad total <span style="color:var(--gacov-error)">*</span></label>
                @if($selectedProduct)
                <p style="font-size:12px;color:var(--gacov-text-muted);margin-bottom:var(--space-2)">
                    Stock actual: <strong>{{ number_format((float) $currentStock, 0, ',', '.') }}</strong>. Ingresa la cantidad correcta después del conteo.
                </p>
                @endif
                <input type="number" name="new_quantity"
                       class="form-input {{ $errors->has('new_quantity') ? 'is-invalid' : '' }}"
                       value="{{ old('new_quantity') }}"
                       min="0" step="1" required
                       placeholder="Cantidad real contada...">
                @error('new_quantity')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Motivo del ajuste <span style="color:var(--gacov-error)">*</span></label>
                <textarea name="reason" class="form-input {{ $errors->has('reason') ? 'is-invalid' : '' }}"
                          rows="3" required
                          placeholder="Ej: Conteo físico semanal, merma detectada, ingreso de mercancía...">{{ old('reason') }}</textarea>
                @error('reason')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div style="display:flex;gap:var(--space-3);padding-top:var(--space-4);border-top:1px solid var(--gacov-border)">
                <button type="submit" class="btn btn-primary" style="width:auto">Aplicar ajuste</button>
                <a href="{{ route('inventory.warehouse') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
