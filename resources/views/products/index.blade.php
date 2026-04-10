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
            <div class="inventory-filter-card__title">Filtrar catalogo</div>
            <div class="inventory-filter-card__copy">Busca por ID, codigo, WorldOffice, nombre o categoria.</div>
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
                <th>ID</th>
                <th>Código</th>
                <th>WO</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th style="text-align:right">Alerta mín.</th>
                <th>Estado</th>
                @canany(['products.edit', 'products.delete'])
                <th>Acciones</th>
                @endcanany
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td><strong>#{{ $product->id }}</strong></td>
                <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $product->code }}</code></td>
                <td>{{ $product->worldoffice_code ?: '—' }}</td>
                <td>
                    <div class="inventory-table-product">
                        <span class="inventory-table-product__name">{{ $product->name }}</span>
                        <span class="inventory-table-product__meta">{{ $product->unit_of_measure }}</span>
                    </div>
                </td>
                <td>
                    @php
                    $catBadge = match($product->category) {
                        'snack'            => ['badge-info', 'Snacks'],
                        'bebida_fria'      => ['badge-neutral', 'Bebidas frías'],
                        'bebida_caliente'  => ['badge-warning', 'Bebidas calientes'],
                        'insumo'           => ['badge-warning', 'Insumos'],
                        default            => ['badge-neutral', $product->category],
                    };
                    @endphp
                    <span class="badge {{ $catBadge[0] }}">{{ $catBadge[1] }}</span>
                </td>
                <td>{{ $product->unit_of_measure }}</td>
                <td style="text-align:right">{{ number_format((float) $product->min_stock_alert, 0, ',', '.') }}</td>
                <td>
                    <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-neutral' }}">
                        {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                @canany(['products.edit', 'products.delete'])
                <td>
                    <div style="display:flex;gap:var(--space-2);align-items:center;flex-wrap:wrap">
                        @can('products.edit')
                        <a href="{{ route('products.edit', $product) }}" class="inventory-action-link">Editar</a>
                        <form method="POST" action="{{ route('products.toggle', $product) }}" style="display:inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                style="padding:7px 12px;background:{{ $product->is_active ? 'rgba(245,158,11,.12)' : 'rgba(16,185,129,.12)' }};color:{{ $product->is_active ? 'var(--gacov-warning)' : 'var(--gacov-success)' }};border:none;border-radius:var(--radius-md);font-size:12px;font-weight:700;cursor:pointer">
                                {{ $product->is_active ? 'Desactivar' : 'Reactivar' }}
                            </button>
                        </form>
                        @endcan
                        @can('products.delete')
                        @if($product->is_active)
                        <form method="POST" action="{{ route('products.destroy', $product) }}" style="display:inline"
                              onsubmit="return confirm('¿Desactivar el producto «{{ $product->name }}»?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                style="padding:7px 12px;background:rgba(239,68,68,.1);color:var(--gacov-error);border:none;border-radius:var(--radius-md);font-size:12px;font-weight:700;cursor:pointer">
                                Eliminar
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
