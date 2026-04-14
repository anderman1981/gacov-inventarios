@extends('layouts.app')
@section('title', 'Ajuste de stock')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">{{ $adjustmentContext['eyebrow'] }}</span>
            <h1 class="inventory-hero__title">{{ $adjustmentContext['title'] }}</h1>
            <p class="inventory-hero__subtitle">{{ $adjustmentContext['subtitle'] }}</p>
        </div>
        <div class="inventory-hero__actions">
            <a href="{{ route($adjustmentContext['back_route']) }}" class="btn" style="background:#eaf1f7;color:#0f172a">{{ $adjustmentContext['back_label'] }}</a>
        </div>
    </div>
</section>

<div class="inventory-card-grid" style="grid-template-columns:minmax(0,700px);">
    <section class="inventory-location-card">
        <div class="inventory-location-card__head">
            <div>
                <div class="inventory-location-card__title">Nuevo ajuste</div>
                <p class="inventory-location-card__subtitle">{{ $warehouse->name }}</p>
            </div>
            <span class="badge {{ $adjustmentContext['is_initial_load'] ? 'badge-success' : 'badge-warning' }}">
                {{ $adjustmentContext['is_initial_load'] ? 'Carga inicial' : 'Ajuste con observación' }}
            </span>
        </div>
        <div class="inventory-location-card__body">
            <form method="POST" action="{{ route('inventory.adjust.store') }}">
                @csrf
                <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">

                <div class="alert {{ $adjustmentContext['requires_reason'] ? 'alert-warning' : 'alert-info' }}" style="margin-bottom:var(--space-5)">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-3a1 1 0 10-2 0 1 1 0 002 0zm-2 3a1 1 0 000 2v2a1 1 0 102 0v-2a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                    <div>
                        @if($adjustmentContext['requires_reason'])
                            <strong>Este movimiento ya no es carga inicial.</strong>
                            Debes dejar observación del motivo del ajuste y el sistema avisará al admin para revisión.
                        @else
                            <strong>Estás registrando la carga inicial.</strong>
                            Puedes dejar una observación opcional para documentar el arranque de esta bodega.
                        @endif
                    </div>
                </div>

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
                    <label class="form-label">
                        {{ $adjustmentContext['reason_label'] }}
                        @if($adjustmentContext['requires_reason'])
                            <span style="color:var(--gacov-error)">*</span>
                        @endif
                    </label>
                    <textarea name="reason" class="form-input {{ $errors->has('reason') ? 'is-invalid' : '' }}"
                              rows="3"
                              {{ $adjustmentContext['requires_reason'] ? 'required' : '' }}
                              placeholder="Ej: Conteo físico semanal, merma detectada, ingreso de mercancía...">{{ old('reason') }}</textarea>
                    <p style="font-size:12px;color:var(--gacov-text-muted);margin-top:6px">{{ $adjustmentContext['reason_help'] }}</p>
                    @error('reason')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="inventory-filter-actions" style="padding-top:var(--space-4);border-top:1px solid #dbe5ef">
                    <button type="submit" class="btn btn-primary" style="width:auto">{{ $adjustmentContext['submit_label'] }}</button>
                    <a href="{{ route($adjustmentContext['back_route']) }}" class="btn" style="width:auto;background:#eaf1f7;color:#0f172a">Cancelar</a>
                </div>
            </form>
        </div>
    </section>
</div>
</div>
@endsection
