@extends('layouts.app')
@section('title', 'Ajuste de stock')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Correccion manual</span>
            <h1 class="inventory-hero__title">Ajuste de stock</h1>
            <p class="inventory-hero__subtitle">Usa esta pantalla para corregir el inventario final de la bodega principal después de un conteo físico o una novedad operacional.</p>
        </div>
        <div class="inventory-hero__actions">
            <a href="{{ route('inventory.warehouse') }}" class="btn" style="background:#eaf1f7;color:#0f172a">Volver a bodega</a>
        </div>
    </div>
</section>

<div class="inventory-card-grid" style="grid-template-columns:minmax(0,700px);">
    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Nuevo ajuste</div>
                <p class="inventory-location-card__subtitle">{{ $mainWarehouse->name }}</p>
            </div>
        </div>
        <div class="inventory-location-card__body">
            <form method="POST" action="{{ route('inventory.adjust.store') }}">
                @csrf
                <input type="hidden" name="warehouse_id" value="{{ $mainWarehouse->id }}">

                <div class="form-group">
                    <label class="form-label">Producto <span style="color:var(--gacov-error)">*</span></label>
                    <select name="product_id" class="form-input {{ $errors->has('product_id') ? 'is-invalid' : '' }}" required>
                        <option value="">Seleccionar producto...</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ (old('product_id', $selectedProduct?->id) == $product->id) ? 'selected' : '' }}>
                            {{ $product->code }} — {{ $product->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('product_id')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                @if($selectedProduct)
                <div class="inventory-meta-strip" style="margin-bottom:var(--space-5)">
                    <div class="inventory-meta-card">
                        <div class="inventory-meta-card__label">Stock actual</div>
                        <div class="inventory-meta-card__value">{{ number_format((int) $currentStock, 0, ',', '.') }}</div>
                    </div>
                    <div class="inventory-meta-card">
                        <div class="inventory-meta-card__label">Unidad</div>
                        <div class="inventory-meta-card__value">{{ $selectedProduct->unit_of_measure }}</div>
                    </div>
                </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Nueva cantidad total <span style="color:var(--gacov-error)">*</span></label>
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

                <div class="inventory-filter-actions" style="padding-top:var(--space-4);border-top:1px solid #dbe5ef">
                    <button type="submit" class="btn btn-primary" style="width:auto">Aplicar ajuste</button>
                    <a href="{{ route('inventory.warehouse') }}" class="btn" style="width:auto;background:#eaf1f7;color:#0f172a">Cancelar</a>
                </div>
            </form>
        </div>
    </section>
</div>
</div>
@endsection
