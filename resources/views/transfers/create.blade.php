@extends('layouts.app')
@section('title', 'Nuevo Traslado')

@section('content')
<div class="page-header" style="margin-bottom:var(--space-6)">
    <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-2)">
        <a href="{{ route('transfers.index') }}" style="color:var(--gacov-text-muted);text-decoration:none;font-size:13px">
            ← Traslados
        </a>
    </div>
    <h1 class="page-title">Nueva orden de traslado</h1>
    <p class="page-subtitle">Selecciona origen, destino y los productos a trasladar</p>
</div>

<form method="POST" action="{{ route('transfers.store') }}" id="transfer-form">
    @csrf

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:var(--space-5)">
        <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <div>
            <strong>Corrige los siguientes errores:</strong>
            <ul style="margin:var(--space-2) 0 0 var(--space-4);padding:0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Panel de bodegas --}}
    <div class="panel" style="margin-bottom:var(--space-5)">
        <div class="panel-header">
            <h2 class="panel-title">Información del traslado</h2>
        </div>
        <div class="panel-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5)">
                <div class="form-group">
                    <label class="form-label" for="origin_warehouse_id">Bodega origen <span style="color:var(--gacov-error)">*</span></label>
                    <select name="origin_warehouse_id" id="origin_warehouse_id" class="form-input @error('origin_warehouse_id') is-invalid @enderror" onchange="updateStockDisplay()">
                        <option value="">— Seleccionar bodega —</option>
                        @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('origin_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                            @php
                                $typeLabel = match($warehouse->type) {
                                    'bodega'   => 'Bodega',
                                    'vehiculo' => 'Vehículo',
                                    'maquina'  => 'Máquina',
                                    default    => $warehouse->type,
                                };
                            @endphp
                            ({{ $typeLabel }})
                        </option>
                        @endforeach
                    </select>
                    @error('origin_warehouse_id')
                    <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="destination_warehouse_id">Bodega destino <span style="color:var(--gacov-error)">*</span></label>
                    <select name="destination_warehouse_id" id="destination_warehouse_id" class="form-input @error('destination_warehouse_id') is-invalid @enderror">
                        <option value="">— Seleccionar bodega —</option>
                        @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('destination_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                            @php
                                $typeLabel = match($warehouse->type) {
                                    'bodega'   => 'Bodega',
                                    'vehiculo' => 'Vehículo',
                                    'maquina'  => 'Máquina',
                                    default    => $warehouse->type,
                                };
                            @endphp
                            ({{ $typeLabel }})
                        </option>
                        @endforeach
                    </select>
                    @error('destination_warehouse_id')
                    <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group" style="margin-top:var(--space-4)">
                <label class="form-label" for="notes">Notas / observaciones</label>
                <textarea name="notes" id="notes" class="form-input @error('notes') is-invalid @enderror"
                    rows="2" maxlength="500" placeholder="Observaciones opcionales sobre este traslado...">{{ old('notes') }}</textarea>
                @error('notes')
                <span class="form-error">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->can('inventory.load_excel'))
    <div style="margin-bottom:var(--space-5)">
        <livewire:transfers.photo-to-table-import />
    </div>
    @endif

    {{-- Panel de productos --}}
    <div class="panel" style="margin-bottom:var(--space-5)">
        <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between">
            <h2 class="panel-title">Productos a trasladar</h2>
            <span style="font-size:12px;color:var(--gacov-text-muted)">
                Ingresa cantidad 0 para excluir el producto
            </span>
        </div>

        @if($errors->has('items'))
        <div style="padding:var(--space-3) var(--space-5);background:rgba(239,68,68,.08);border-bottom:1px solid rgba(239,68,68,.2)">
            <span style="color:var(--gacov-error);font-size:13px">{{ $errors->first('items') }}</span>
        </div>
        @endif

        <div class="panel-body" style="padding:var(--space-5)">
            <div style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:end;margin-bottom:var(--space-4)">
                <div class="form-group" style="flex:1;min-width:260px;margin-bottom:0">
                    <label class="form-label" for="transfer-product-search">Buscar producto</label>
                    <input
                        type="text"
                        id="transfer-product-search"
                        class="form-input"
                        placeholder="Buscar por SKU, codigo o nombre..."
                        autocomplete="off">
                </div>
                <div class="form-group" style="min-width:140px;margin-bottom:0">
                    <label class="form-label" for="transfer-products-per-page">Mostrar</label>
                    <select id="transfer-products-per-page" class="form-input">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div style="margin-left:auto;font-size:12px;color:var(--gacov-text-muted)" id="transfer-products-summary">
                    Cargando productos...
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th style="text-align:center">Stock disponible</th>
                        <th style="text-align:center;width:160px">Cantidad a trasladar</th>
                    </tr>
                </thead>
                <tbody id="transfer-products-body">
                    @foreach($products as $index => $product)
                    @php
                        $oldQty = old("items.{$index}.quantity_requested", 0);
                        $productCode = strtoupper(trim((string) $product->code));
                        $normalizedProductCode = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $productCode));
                    @endphp
                    <tr
                        id="row-{{ $product->id }}"
                        class="transfer-product-row"
                        data-name="{{ mb_strtolower($product->name) }}"
                        data-sku="{{ mb_strtolower($productCode) }}"
                        data-code="{{ $productCode }}"
                        data-normalized-code="{{ $normalizedProductCode }}"
                        data-product-id="{{ $product->id }}"
                        data-unit="{{ mb_strtolower($product->unit) }}">
                        <td>
                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $product->id }}">
                            <code style="font-size:12px;color:var(--gacov-primary)">{{ $productCode }}</code>
                        </td>
                        <td>
                            <span style="font-weight:500">{{ $product->name }}</span>
                            <br>
                            <span style="font-size:11px;color:var(--gacov-text-muted)">{{ $product->unit }}</span>
                        </td>
                        <td style="text-align:center">
                            {{-- Stock por bodega, renderizado por JS --}}
                            @foreach($warehouses as $wh)
                            <span class="warehouse-stock"
                                  data-warehouse-id="{{ $wh->id }}"
                                  data-product-id="{{ $product->id }}"
                                  data-quantity="{{ $allStocks->get($wh->id)?->get($product->id)?->quantity ?? 0 }}"
                                  style="display:none;font-weight:700;font-size:13px">
                            </span>
                            @endforeach
                            <span id="stock-display-{{ $product->id }}" style="font-size:12px;color:var(--gacov-text-muted)">
                                Selecciona bodega origen
                            </span>
                        </td>
                        <td style="text-align:center">
                            <input type="number"
                                   name="items[{{ $index }}][quantity_requested]"
                                   value="{{ $oldQty }}"
                                   min="0"
                                   step="1"
                                   data-quantity-input="true"
                                   class="form-input"
                                   style="width:100px;text-align:center;margin:0 auto"
                                   placeholder="0">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div id="transfer-products-empty" style="display:none;text-align:center;padding:var(--space-8) 0;color:var(--gacov-text-muted)">
                No se encontraron productos con ese criterio de busqueda.
            </div>

            <div id="transfer-products-pagination" style="display:flex;justify-content:space-between;gap:var(--space-3);align-items:center;flex-wrap:wrap;margin-top:var(--space-4)">
                <div style="font-size:12px;color:var(--gacov-text-muted)" id="transfer-products-page-info"></div>
                <div id="transfer-products-page-buttons" style="display:flex;gap:var(--space-2);flex-wrap:wrap"></div>
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
        <a href="{{ route('transfers.index') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-secondary)">
            Cancelar
        </a>
        <button type="submit" class="btn btn-primary" style="width:auto">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Crear orden de traslado
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
'use strict';

// Datos de stock por bodega y producto
const allStocks = @json(
    $warehouses->mapWithKeys(fn($wh) => [
        $wh->id => $products->mapWithKeys(fn($p) => [
            $p->id => $allStocks->get($wh->id)?->get($p->id)?->quantity ?? 0
        ])
    ])
);

function updateStockDisplay() {
    const originId = parseInt(document.getElementById('origin_warehouse_id').value);
    const warehouseStocks = allStocks[originId] ?? {};

    document.querySelectorAll('[id^="stock-display-"]').forEach(el => {
        const productId = parseInt(el.id.replace('stock-display-', ''));
        const qty = warehouseStocks[productId] ?? 0;

        if (originId) {
            const color = qty < 5 ? 'var(--gacov-error)' : (qty < 20 ? 'var(--gacov-warning)' : 'var(--gacov-success)');
            el.innerHTML = `<strong style="color:${color}">${Math.trunc(qty)}</strong> <span style="color:var(--gacov-text-muted);font-size:11px">uds.</span>`;
        } else {
            el.textContent = 'Selecciona bodega origen';
        }
    });
}

// Inicializar si ya hay valor (old input)
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('origin_warehouse_id').value) {
        updateStockDisplay();
    }

    const productRows = Array.from(document.querySelectorAll('.transfer-product-row'));
    const productSearchInput = document.getElementById('transfer-product-search');
    const perPageSelect = document.getElementById('transfer-products-per-page');
    const summaryElement = document.getElementById('transfer-products-summary');
    const emptyElement = document.getElementById('transfer-products-empty');
    const pageInfoElement = document.getElementById('transfer-products-page-info');
    const pageButtonsElement = document.getElementById('transfer-products-page-buttons');

    let currentPage = 1;

    function getFilteredRows() {
        const searchTerm = productSearchInput.value.trim().toLowerCase();

        return productRows.filter((row) => {
            if (searchTerm === '') {
                return true;
            }

            const haystack = [
                row.dataset.name ?? '',
                row.dataset.sku ?? '',
                (row.dataset.code ?? '').toLowerCase(),
                row.dataset.unit ?? '',
            ].join(' ');

            return haystack.includes(searchTerm);
        });
    }

    function renderPagination(totalPages) {
        pageButtonsElement.innerHTML = '';

        if (totalPages <= 1) {
            pageInfoElement.textContent = '';
            return;
        }

        const previousButton = document.createElement('button');
        previousButton.type = 'button';
        previousButton.className = 'btn';
        previousButton.style.width = 'auto';
        previousButton.style.background = 'var(--gacov-bg-elevated)';
        previousButton.style.color = 'var(--gacov-text-primary)';
        previousButton.textContent = 'Anterior';
        previousButton.disabled = currentPage === 1;
        previousButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updateProductPagination();
            }
        });
        pageButtonsElement.appendChild(previousButton);

        for (let page = 1; page <= totalPages; page++) {
            const pageButton = document.createElement('button');
            pageButton.type = 'button';
            pageButton.className = 'btn';
            pageButton.style.width = 'auto';
            pageButton.style.minWidth = '42px';
            pageButton.style.background = page === currentPage ? 'var(--gacov-primary)' : 'var(--gacov-bg-elevated)';
            pageButton.style.color = page === currentPage ? '#04111d' : 'var(--gacov-text-primary)';
            pageButton.textContent = String(page);
            pageButton.addEventListener('click', () => {
                currentPage = page;
                updateProductPagination();
            });
            pageButtonsElement.appendChild(pageButton);
        }

        const nextButton = document.createElement('button');
        nextButton.type = 'button';
        nextButton.className = 'btn';
        nextButton.style.width = 'auto';
        nextButton.style.background = 'var(--gacov-bg-elevated)';
        nextButton.style.color = 'var(--gacov-text-primary)';
        nextButton.textContent = 'Siguiente';
        nextButton.disabled = currentPage === totalPages;
        nextButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                updateProductPagination();
            }
        });
        pageButtonsElement.appendChild(nextButton);
    }

    function updateProductPagination() {
        const filteredRows = getFilteredRows();
        const perPage = parseInt(perPageSelect.value, 10) || 25;
        const totalRows = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
        const startIndex = (currentPage - 1) * perPage;
        const endIndex = startIndex + perPage;

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        productRows.forEach((row) => {
            row.style.display = 'none';
        });

        filteredRows.slice(startIndex, endIndex).forEach((row) => {
            row.style.display = '';
        });

        if (totalRows === 0) {
            emptyElement.style.display = 'block';
            summaryElement.textContent = '0 productos encontrados';
            pageInfoElement.textContent = '';
            pageButtonsElement.innerHTML = '';
            return;
        }

        emptyElement.style.display = 'none';

        const visibleStart = startIndex + 1;
        const visibleEnd = Math.min(endIndex, totalRows);

        summaryElement.textContent = `Mostrando ${visibleStart}-${visibleEnd} de ${totalRows} productos`;
        pageInfoElement.textContent = `Pagina ${currentPage} de ${totalPages}`;

        renderPagination(totalPages);
    }

    productSearchInput.addEventListener('input', () => {
        currentPage = 1;
        updateProductPagination();
    });

    perPageSelect.addEventListener('change', () => {
        currentPage = 1;
        updateProductPagination();
    });

    updateProductPagination();
});

window.addEventListener('transfer-photo-import-applied', (event) => {
    const rows = event.detail.rows ?? [];
    const missingCodes = event.detail.missingCodes ?? [];

    function normalizeProductCode(code) {
        return String(code ?? '')
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');
    }

    function codeVariants(code) {
        const raw = String(code ?? '').trim().toUpperCase();
        const normalized = normalizeProductCode(code);
        const variants = [];

        if (raw !== '') {
            variants.push(raw);
        }

        if (normalized !== '' && !variants.includes(normalized)) {
            variants.push(normalized);
        }

        if (/^\d+$/.test(normalized)) {
            const withoutLeadingZeroes = normalized.replace(/^0+/, '') || '0';

            if (!variants.includes(withoutLeadingZeroes)) {
                variants.push(withoutLeadingZeroes);
            }
        }

        return variants;
    }

    const rowMap = new Map();

    Array.from(document.querySelectorAll('.transfer-product-row')).forEach((row) => {
        codeVariants(row.dataset.code ?? '').forEach((variant) => {
            if (!rowMap.has(variant)) {
                rowMap.set(variant, row);
            }
        });
    });

    let appliedCount = 0;

    rows.forEach((row) => {
        const rowCandidates = [
            ...(codeVariants(row.catalogCode ?? '')),
            ...(codeVariants(row.code ?? '')),
        ];

        const tableRow = rowCandidates
            .map((candidate) => rowMap.get(candidate))
            .find((candidate) => candidate !== undefined);

        if (!tableRow) {
            return;
        }

        const input = tableRow.querySelector('[data-quantity-input="true"]');

        if (!input) {
            return;
        }

        input.value = String(Math.max(0, Number(row.quantity ?? 0)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        appliedCount += 1;
    });

    const message = appliedCount > 0
        ? `Se aplicaron ${appliedCount} producto(s) al formulario desde la planilla.`
        : 'No se pudo aplicar ninguna fila al formulario.';

    const existingAlert = document.getElementById('transfer-photo-import-feedback');
    existingAlert?.remove();

    const feedback = document.createElement('div');
    feedback.id = 'transfer-photo-import-feedback';
    feedback.className = appliedCount > 0 ? 'alert alert-success' : 'alert alert-error';
    feedback.style.marginBottom = 'var(--space-5)';
    feedback.innerHTML = `
        <div>
            <strong>${message}</strong>
            ${missingCodes.length > 0 ? `<div style="margin-top:6px;font-size:12px">Códigos no encontrados en el catálogo activo: ${missingCodes.join(', ')}</div>` : ''}
        </div>
    `;

    const form = document.getElementById('transfer-form');
    form.prepend(feedback);
    feedback.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
@endpush
