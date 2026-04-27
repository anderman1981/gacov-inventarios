@extends('layouts.app')
@section('title', 'Productos')

@section('content')
<div class="inventory-shell inventory-shell--light">
@include('inventory.partials.section-nav')

<section class="inventory-hero">
    <div class="inventory-hero__grid">
        <div>
            <span class="inventory-hero__eyebrow">Catalogo maestro</span>
            <h1 class="inventory-hero__title">Productos</h1>
            <p class="inventory-hero__subtitle">
                Catalogo base del sistema. Aqui defines el codigo, nombre, categoria y unidad que usan bodega principal, vehiculos y maquinas.
            </p>
            <div class="inventory-hero__badges">
                <span class="badge badge-success">Activos: {{ number_format($activeCount, 0, ',', '.') }}</span>
                <span class="badge badge-neutral">Inactivos: {{ number_format($inactiveCount, 0, ',', '.') }}</span>
                <span class="badge badge-info">Total catalogo: {{ number_format($totalProducts, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="inventory-hero__actions">
            @can('products.create')
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                Nuevo producto
            </a>
            @endcan
        </div>
    </div>
</section>

<div class="inventory-status-tabs">
    <a href="{{ route('products.index', array_merge(request()->except(['page', 'status']), ['status' => 'active'])) }}" class="inventory-status-tabs__item {{ $selectedTab === 'active' ? 'active' : '' }}">
        Activos
        <span class="inventory-status-tabs__count">{{ number_format($activeCount, 0, ',', '.') }}</span>
    </a>
    <a href="{{ route('products.index', array_merge(request()->except(['page', 'status']), ['status' => 'inactive'])) }}" class="inventory-status-tabs__item {{ $selectedTab === 'inactive' ? 'active' : '' }}">
        Inactivos
        <span class="inventory-status-tabs__count">{{ number_format($inactiveCount, 0, ',', '.') }}</span>
    </a>
</div>

<section class="inventory-filter-card">
    <div class="inventory-filter-card__header">
        <div>
            <div class="inventory-filter-card__title" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                Filtrar catálogo
                <button type="button" class="amr-icon-button amr-tooltip-trigger inventory-help-pulse" data-tooltip="Busca por ID, código, WorldOffice, nombre o categoría" aria-label="Ayuda del filtro">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <div class="inventory-filter-card__copy">El catálogo base se usa en bodega principal, vehículos y máquinas.</div>
        </div>
        <div class="badge badge-neutral">Resultados en esta vista: {{ number_format($products->total(), 0, ',', '.') }}</div>
    </div>
    <div class="inventory-filter-card__body">
        <form method="GET" action="{{ route('products.index') }}" class="inventory-filter-form">
            <input type="hidden" name="status" value="{{ $selectedTab }}">
            <div class="form-group" style="flex:1;min-width:220px;margin-bottom:0">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-input" placeholder="ID, código, WorldOffice o nombre..." value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label class="form-label">Categoría</label>
                <select name="category" class="form-input">
                    <option value="">Todas</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="min-width:130px;margin-bottom:0">
                <label class="form-label">Por página</label>
                <select name="per_page" class="form-input">
                    @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inventory-filter-actions">
                <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
                <a href="{{ route('products.index', ['status' => $selectedTab]) }}" class="inventory-filter-reset">Limpiar</a>
            </div>
        </form>
    </div>
</section>

<section class="panel inventory-table-panel">
    <div class="inventory-results-bar">
        <span>Vista: <strong>{{ $selectedTab === 'active' ? 'Activos' : 'Inactivos / obsoletos' }}</strong></span>
        <span>En esta página: <strong>{{ number_format($products->count(), 0, ',', '.') }}</strong> registros</span>
    </div>

    @if($products->count() > 0)
    <div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-code">COD</th>
                <th class="col-wo">WO</th>
                <th class="col-product">Producto</th>
                <th class="col-category">CAT</th>
                <th class="col-unit">Unidad</th>
                <th class="col-min-stock" style="text-align:right">Alerta mín.</th>
                <th class="col-status">Estado</th>
                @canany(['products.edit', 'products.delete'])
                <th class="col-actions">Acciones</th>
                @endcanany
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td class="col-id"><strong>#{{ $product->id }}</strong></td>
                <td class="col-code"><code style="font-size:12px;color:var(--gacov-primary)">{{ $product->code }}</code></td>
                <td class="col-wo">{{ $product->worldoffice_code ?: '—' }}</td>
                <td class="col-product">
                    <div class="inventory-table-product">
                        <span class="inventory-table-product__name">{{ $product->name }}</span>
                        <span class="inventory-table-product__meta">{{ $product->unit_of_measure }}</span>
                    </div>
                </td>
                <td class="col-category">
                    @php
                    $catBadge = match($product->category) {
                        'snack'            => ['badge-info', 'SN', 'Snacks'],
                        'bebida_fria'      => ['badge-neutral', 'BF', 'Bebidas frías'],
                        'bebida_caliente'  => ['badge-warning', 'BC', 'Bebidas calientes'],
                        'insumo'           => ['badge-warning', 'IN', 'Insumos'],
                        default            => ['badge-neutral', strtoupper(mb_substr((string) $product->category, 0, 2)), (string) $product->category],
                    };
                    @endphp
                    <span class="badge {{ $catBadge[0] }} inventory-category-badge" title="{{ $catBadge[2] }}">{{ $catBadge[1] }}</span>
                </td>
                <td class="col-unit">{{ $product->unit_of_measure }}</td>
                <td class="col-min-stock" style="text-align:right">{{ number_format((float) $product->min_stock_alert, 0, ',', '.') }}</td>
                <td class="col-status">
                    <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-neutral' }} inventory-status-pill">
                        {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                @canany(['products.edit', 'products.delete'])
                <td class="col-actions">
                    <div class="inventory-row-actions">
                        @can('products.edit')
                        <a href="{{ route('products.edit', $product) }}" class="amr-icon-button amr-icon-button--primary amr-tooltip-trigger" data-tooltip="Editar producto" aria-label="Editar producto">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3a2 2 0 012.828 0l.586.586a2 2 0 010 2.828l-8.293 8.293a1 1 0 01-.465.263l-3.5.875a1 1 0 01-1.213-1.213l.875-3.5a1 1 0 01.263-.465L13.586 3zM12 5.414L7.5 9.914 7.086 11.5l1.586-.414L13.172 6l-1.172-1.172z"/></svg>
                        </a>
                        <form method="POST" action="{{ route('products.toggle', $product) }}" style="display:inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="amr-icon-button amr-tooltip-trigger {{ $product->is_active ? 'amr-icon-button--warning' : 'amr-icon-button--primary' }}"
                                data-tooltip="{{ $product->is_active ? 'Desactivar producto' : 'Reactivar producto' }}"
                                aria-label="{{ $product->is_active ? 'Desactivar producto' : 'Reactivar producto' }}">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    @if($product->is_active)
                                    <path d="M10 2a8 8 0 100 16A8 8 0 0010 2zm-1 4a1 1 0 112 0v4a1 1 0 11-2 0V6zm1 8a1.25 1.25 0 110-2.5A1.25 1.25 0 0110 14z"/>
                                    @else
                                    <path d="M10 2a8 8 0 100 16A8 8 0 0010 2zm1 4a1 1 0 10-2 0v4a1 1 0 102 0V6zm-1 8a1.25 1.25 0 100-2.5A1.25 1.25 0 009 14z"/>
                                    @endif
                                </svg>
                            </button>
                        </form>
                        @endcan
                        @can('products.delete')
                        @if($product->is_active)
                        <form method="POST" action="{{ route('products.destroy', $product) }}" style="display:inline"
                              onsubmit="return confirm('¿Desactivar el producto «{{ $product->name }}»?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="amr-icon-button amr-icon-button--danger amr-tooltip-trigger"
                                data-tooltip="Eliminar producto"
                                aria-label="Eliminar producto">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 2a2 2 0 00-2 2v1H4a1 1 0 100 2h.5l.8 9.2A2 2 0 007.3 18h5.4a2 2 0 001.99-1.8L15.5 7H16a1 1 0 100-2h-2V4a2 2 0 00-2-2H8zm2 3a1 1 0 10-2 0v1h2V5zm-2 4a1 1 0 012 0v5a1 1 0 11-2 0V9z" clip-rule="evenodd"/></svg>
                            </button>
                        </form>
                        @endif
                        @endcan
                    </div>
                </td>
                @endcanany
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    @if($products->hasPages())
    <div class="inventory-pagination">
        <div class="inventory-pagination__meta">
            Mostrando {{ $products->firstItem() }}-{{ $products->lastItem() }} de {{ $products->total() }} registros
        </div>
        <div>{{ $products->links() }}</div>
    </div>
    @endif
    @else
    <div class="inventory-empty">
        <p class="inventory-empty__title">Sin resultados en esta vista</p>
        <p>No se encontraron productos con los filtros actuales.</p>
    </div>
    @endif
</section>
</div>
@endsection
