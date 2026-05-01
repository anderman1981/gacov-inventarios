@extends('layouts.app')

@section('title', 'Tabla temporal de compra')

@section('content')
<div class="inventory-shell inventory-shell--light purchase-import-shell">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Tabla temporal</span>
            <h1 class="inventory-hero__title">Compra #{{ $batch->id }}</h1>
            <p class="inventory-hero__subtitle">{{ $batch->original_file_name }}. Revisa las filas antes de cargar esta compra a {{ $batch->warehouse?->name ?? 'bodega' }}.</p>
            <div class="inventory-hero__badges">
                <span class="badge {{ $batch->status === 'procesado' ? 'badge-success' : ($batch->status === 'descartado' ? 'badge-neutral' : 'badge-warning') }}">{{ ucfirst($batch->status) }}</span>
                <span class="badge badge-info">{{ number_format($batch->valid_rows, 0, ',', '.') }} válidas</span>
                <span class="badge {{ $batch->error_rows > 0 ? 'badge-error' : 'badge-success' }}">{{ number_format($batch->error_rows, 0, ',', '.') }} errores</span>
            </div>
        </div>
        <div class="inventory-hero__actions">
            <a href="{{ route('inventory.purchases.index') }}" class="btn" style="background:#eaf1f7;color:#0f172a">Volver a compras</a>
            @if($batch->status === 'borrador')
                <form method="POST" action="{{ route('inventory.purchases.destroy', $batch) }}" onsubmit="return confirm('¿Descartar esta compra temporal? El inventario no se modificará.')" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn" style="background:#fee2e2;color:#991b1b;width:auto">Descartar</button>
                </form>
            @endif
        </div>
    </div>
</section>

<div class="kpi-grid purchase-import-kpi-grid">
    <div class="kpi-card" style="--kpi-accent:#00D4FF;--kpi-bg:rgba(0,212,255,.1)">
        <div class="kpi-value">{{ number_format($batch->total_rows, 0, ',', '.') }}</div>
        <div class="kpi-label">Filas CSV</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#10B981;--kpi-bg:rgba(16,185,129,.1)">
        <div class="kpi-value">{{ number_format($batch->total_units, 0, ',', '.') }}</div>
        <div class="kpi-label">Unidades por cargar</div>
    </div>
    <div class="kpi-card" style="--kpi-accent:#7C3AED;--kpi-bg:rgba(124,58,237,.1)">
        <div class="kpi-value">${{ number_format((float) $batch->total_cost, 0, ',', '.') }}</div>
        <div class="kpi-label">Costo estimado</div>
    </div>
</div>

<datalist id="purchase-product-options">
    @foreach($productOptions as $product)
        <option value="{{ $product->code }}">{{ $product->code }} · {{ $product->name }}</option>
        @if($product->worldoffice_code)
            <option value="{{ $product->worldoffice_code }}">{{ $product->worldoffice_code }} · {{ $product->name }}</option>
        @endif
        @if($product->supplier_sku)
            <option value="{{ $product->supplier_sku }}">{{ $product->supplier_sku }} · {{ $product->name }}</option>
        @endif
    @endforeach
</datalist>

<section class="panel inventory-table-panel purchase-import-workspace">
    <div class="purchase-import-workspace__column purchase-import-workspace__column--errors">
        <div class="inventory-results-bar">
            <span>Correcciones pendientes</span>
            <span class="badge {{ (int) ($errorTabCounts['all'] ?? 0) > 0 ? 'badge-error' : 'badge-success' }}">{{ number_format((int) ($errorTabCounts['all'] ?? 0), 0, ',', '.') }} por revisar</span>
        </div>

        <div class="purchase-import-tab-bar" role="tablist" aria-label="Tabs de errores">
            <a href="{{ route('inventory.purchases.show', ['purchaseImport' => $batch, 'error_tab' => 'all', 'row_view' => $selectedRowView]) }}" class="purchase-import-tab {{ $selectedErrorTab === 'all' ? 'active' : '' }}">
                Todos
                <span>{{ number_format((int) ($errorTabCounts['all'] ?? 0), 0, ',', '.') }}</span>
            </a>
            <a href="{{ route('inventory.purchases.show', ['purchaseImport' => $batch, 'error_tab' => 'missing', 'row_view' => $selectedRowView]) }}" class="purchase-import-tab {{ $selectedErrorTab === 'missing' ? 'active' : '' }}">
                Sin match
                <span>{{ number_format((int) ($errorTabCounts['missing'] ?? 0), 0, ',', '.') }}</span>
            </a>
            <a href="{{ route('inventory.purchases.show', ['purchaseImport' => $batch, 'error_tab' => 'other', 'row_view' => $selectedRowView]) }}" class="purchase-import-tab {{ $selectedErrorTab === 'other' ? 'active' : '' }}">
                Otros errores
                <span>{{ number_format((int) ($errorTabCounts['other'] ?? 0), 0, ',', '.') }}</span>
            </a>
        </div>

        @if($unmatchedRows->isNotEmpty())
        <div class="purchase-import-error-list">
            @foreach($unmatchedRows as $row)
            @php
                $missingProductError = str_contains((string) $row->error_message, 'Producto no encontrado');
                $createToggleId = 'purchase-create-toggle-'.$row->id;
                $createNameId = 'purchase-create-name-'.$row->id;
            @endphp
            <form method="POST" action="{{ route('inventory.purchases.rows.update', [$batch, $row]) }}" class="purchase-import-error-card">
                @csrf
                @method('PATCH')

                <div class="purchase-import-error-card__head">
                    <span class="purchase-import-error-card__title">Fila {{ $row->row_number }}</span>
                    <span class="badge {{ $missingProductError ? 'badge-error' : 'badge-warning' }}">{{ $missingProductError ? 'Código sin match' : 'Requiere ajuste' }}</span>
                </div>

                <div class="purchase-import-error-grid">
                    <div>
                        <label class="form-label">Código producto</label>
                        <input type="text" name="product_code" class="form-input" value="{{ $row->product_code }}" list="purchase-product-options" maxlength="60" required>
                    </div>
                    <div>
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="quantity" class="form-input" value="{{ $row->quantity }}" min="1" step="1" required>
                    </div>
                    <div>
                        <label class="form-label">Costo unitario</label>
                        <input type="number" name="unit_cost" class="form-input" value="{{ $row->unit_cost }}" min="0" step="0.01">
                    </div>
                    <div>
                        <label class="form-label">Proveedor</label>
                        <input type="text" name="supplier" class="form-input" value="{{ $row->supplier }}" maxlength="150">
                    </div>
                    <div>
                        <label class="form-label">Factura</label>
                        <input type="text" name="invoice_number" class="form-input" value="{{ $row->invoice_number }}" maxlength="80">
                    </div>
                    <div>
                        <label class="form-label">Fecha</label>
                        <input type="date" name="purchase_date" class="form-input" value="{{ $row->purchase_date?->format('Y-m-d') }}">
                    </div>
                </div>

                @if($missingProductError)
                <div class="purchase-import-create-wrap">
                    <label class="purchase-import-create-toggle" for="{{ $createToggleId }}">
                        <input
                            id="{{ $createToggleId }}"
                            type="checkbox"
                            name="create_missing_product"
                            value="1"
                            data-toggle-create-name="{{ $createNameId }}"
                        >
                        <span>Crear producto si no existe</span>
                    </label>
                    <div id="{{ $createNameId }}" class="purchase-import-create-name" hidden>
                        <label class="form-label">Nombre para crear</label>
                        <input type="text" name="create_product_name" class="form-input" value="{{ $row->product_name }}" maxlength="150" placeholder="Ej: Producto nuevo por compra">
                    </div>
                </div>
                @endif

                <div>
                    <label class="form-label">Observaciones</label>
                    <textarea name="notes" class="form-input" rows="2" maxlength="1000">{{ $row->notes }}</textarea>
                </div>

                <div class="purchase-import-error-card__foot">
                    <span class="purchase-import-error-text">{{ $row->error_message }}</span>
                    <button type="submit" class="btn btn-primary" style="width:auto" @disabled($batch->status !== 'borrador')>Guardar fila</button>
                </div>
            </form>
            @endforeach
        </div>
        @else
        <div class="inventory-empty purchase-import-empty">
            <p class="inventory-empty__title">No hay filas en este tab</p>
            <p>Prueba otro tab de errores o revisa los productos listos en la columna derecha.</p>
        </div>
        @endif
    </div>

    <div class="purchase-import-workspace__column purchase-import-workspace__column--rows">
        <div class="inventory-results-bar">
            <span>Productos que se están cargando</span>
            <span class="badge badge-success">{{ number_format((int) $batch->valid_rows, 0, ',', '.') }} listos</span>
        </div>

        <div class="purchase-import-tab-bar" role="tablist" aria-label="Tabs de filas cargadas">
            <a href="{{ route('inventory.purchases.show', ['purchaseImport' => $batch, 'error_tab' => $selectedErrorTab, 'row_view' => 'valid']) }}" class="purchase-import-tab {{ $selectedRowView === 'valid' ? 'active' : '' }}">
                Solo válidas
                <span>{{ number_format((int) $batch->valid_rows, 0, ',', '.') }}</span>
            </a>
            <a href="{{ route('inventory.purchases.show', ['purchaseImport' => $batch, 'error_tab' => $selectedErrorTab, 'row_view' => 'all']) }}" class="purchase-import-tab {{ $selectedRowView === 'all' ? 'active' : '' }}">
                Todas
                <span>{{ number_format((int) $batch->total_rows, 0, ',', '.') }}</span>
            </a>
        </div>

        @if($rows->count() > 0)
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="text-align:center">Fila</th>
                        <th>Producto</th>
                        <th style="text-align:center">Cantidad</th>
                        <th>Costo unitario</th>
                        <th>Proveedor</th>
                        <th>Factura</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    <tr>
                        <td style="text-align:center">{{ $row->row_number }}</td>
                        <td>
                            <div class="inventory-table-product">
                                <span class="inventory-table-product__name">{{ $row->product?->name ?? $row->product_name ?? 'Producto no encontrado' }}</span>
                                <span class="inventory-table-product__meta"><code>{{ $row->product_code }}</code></span>
                                @if($row->error_message)
                                <span class="inventory-table-product__meta" style="color:#b91c1c">{{ $row->error_message }}</span>
                                @endif
                            </div>
                        </td>
                        <td style="text-align:center">{{ number_format($row->quantity, 0, ',', '.') }}</td>
                        <td>${{ number_format((float) $row->unit_cost, 0, ',', '.') }}</td>
                        <td>{{ $row->supplier ?? '—' }}</td>
                        <td>{{ $row->invoice_number ?? '—' }}</td>
                        <td>{{ $row->purchase_date?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $row->status === 'valida' ? 'badge-success' : 'badge-error' }}">
                                {{ $row->status === 'valida' ? 'Válida' : 'Error' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="purchase-import-pagination">{{ $rows->links() }}</div>
        @else
        <div class="inventory-empty purchase-import-empty">
            <p class="inventory-empty__title">Sin filas para esta vista</p>
            <p>No hay productos para el filtro seleccionado.</p>
        </div>
        @endif
    </div>
</section>

<section class="panel inventory-table-panel purchase-import-actions-panel">
    <div class="purchase-import-actions">
        <form method="POST" action="{{ route('inventory.purchases.notify', $batch) }}" style="margin:0">
            @csrf
            <button type="submit" class="btn purchase-import-actions__btn">Notificar</button>
        </form>

        <form method="POST" action="{{ route('inventory.purchases.verify', $batch) }}" style="margin:0">
            @csrf
            <button type="submit" class="btn purchase-import-actions__btn">Verificar</button>
        </form>

        <form method="POST" action="{{ route('inventory.purchases.validate', $batch) }}" style="margin:0">
            @csrf
            <button type="submit" class="btn purchase-import-actions__btn">Validar</button>
        </form>

        @if($batch->canBeConfirmed())
        <form method="POST" action="{{ route('inventory.purchases.confirm', $batch) }}" onsubmit="return confirm('¿Confirmar y cargar esta compra a bodega?')" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-primary purchase-import-actions__btn-primary">Guardar y cargar compra</button>
        </form>
        @else
        <div class="badge badge-error">Corrige los errores antes de guardar en bodega</div>
        @endif
    </div>
</section>
</div>
@endsection

@push('styles')
<style>
.purchase-import-shell .purchase-import-tab-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
}

.purchase-import-shell .purchase-import-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    border: 1px solid #dbe5ef;
    border-radius: 999px;
    padding: 6px 12px;
    color: #475569;
    background: #f8fbff;
    font-size: 12px;
    font-weight: 600;
}

.purchase-import-shell .purchase-import-tab.active {
    color: #0f172a;
    border-color: #7dd3fc;
    background: #eaf6ff;
}

.purchase-import-shell .purchase-import-error-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 12px;
    padding: 14px;
    background: #f8fbff;
}

.purchase-import-shell .purchase-import-error-card {
    display: grid;
    gap: 10px;
    border: 1px solid #dbe5ef;
    border-radius: 14px;
    background: #ffffff;
    padding: 14px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.purchase-import-shell .purchase-import-error-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
}

.purchase-import-shell .purchase-import-error-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}

.purchase-import-shell .purchase-import-error-card__foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    border-top: 1px dashed #e2e8f0;
    padding-top: 10px;
}

.purchase-import-shell .purchase-import-error-card__foot .btn {
    width: auto;
    min-width: 132px;
}

@media (max-width: 768px) {
    .purchase-import-shell .purchase-import-error-list {
        grid-template-columns: 1fr;
        padding: 10px;
    }

    .purchase-import-shell .purchase-import-error-card {
        padding: 12px;
    }
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const toggles = document.querySelectorAll('[data-toggle-create-name]');

    toggles.forEach((toggle) => {
        const targetId = toggle.getAttribute('data-toggle-create-name');
        const target = targetId ? document.getElementById(targetId) : null;

        if (!target) {
            return;
        }

        const syncVisibility = () => {
            target.hidden = !toggle.checked;
        };

        toggle.addEventListener('change', syncVisibility);
        syncVisibility();
    });
})();
</script>
@endpush
