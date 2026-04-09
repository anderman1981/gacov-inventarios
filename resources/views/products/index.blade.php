@extends('layouts.app')
@section('title', 'Productos')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <h1 class="page-title">Productos</h1>
        <p class="page-subtitle">{{ $totalProducts }} producto(s) registrados</p>
    </div>
    @can('products.create')
    <a href="{{ route('products.create') }}" class="btn btn-primary" style="width:auto">
        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Nuevo producto
    </a>
    @endcan
</div>

{{-- Filtros --}}
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6)">
        <form method="GET" action="{{ route('products.index') }}" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-input" placeholder="ID, código, WorldOffice o nombre..." value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:160px;margin-bottom:0">
                <label class="form-label">Categoría</label>
                <select name="category" class="form-input">
                    <option value="">Todas</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
            <a href="{{ route('products.index') }}" style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px">Limpiar</a>
        </form>
    </div>
</div>

<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-header">
        <span class="panel-title">Productos activos</span>
        <span class="badge badge-success">{{ $activeProducts->count() }}</span>
    </div>
    @if($activeProducts->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código / SKU</th>
                <th>WO</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th style="text-align:right">Precio venta</th>
                <th style="text-align:right">Alerta mín.</th>
                <th>Estado</th>
                @canany(['products.edit', 'products.delete'])
                <th>Acciones</th>
                @endcanany
            </tr>
        </thead>
        <tbody>
            @foreach($activeProducts as $product)
            <tr>
                <td><strong>#{{ $product->id }}</strong></td>
                <td><code style="font-size:12px;color:var(--gacov-primary)">{{ $product->code }}</code></td>
                <td>{{ $product->worldoffice_code ?: '—' }}</td>
                <td><strong>{{ $product->name }}</strong></td>
                <td>
                    @php
                    $catBadge = match($product->category) {
                        'snack'            => ['badge-info', 'Snacks'],
                        'bebida_fria'      => ['badge-neutral', 'Bebidas frías'],
                        'bebida_caliente'  => ['badge-warning', 'Bebidas calientes'],
                        'insumo'           => ['badge-warning', 'Insumos'],
                        default        => ['badge-neutral', $product->category],
                    };
                    @endphp
                    <span class="badge {{ $catBadge[0] }}">{{ $catBadge[1] }}</span>
                </td>
                <td style="color:var(--gacov-text-secondary)">{{ $product->unit_of_measure }}</td>
                <td style="text-align:right;font-weight:600">
                    ${{ number_format((float)$product->unit_price, 0, ',', '.') }}
                </td>
                <td style="text-align:right;color:var(--gacov-text-secondary)">{{ number_format((float)$product->min_stock_alert, 0, ',', '.') }}</td>
                <td>
                    <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-neutral' }}">
                        {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                @canany(['products.edit', 'products.delete'])
                <td>
                    <div style="display:flex;gap:var(--space-2);align-items:center">
                        @can('products.edit')
                        <a href="{{ route('products.edit', $product) }}"
                           title="Editar"
                           style="padding:4px 8px;background:rgba(0,212,255,.1);color:var(--gacov-primary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('products.toggle', $product) }}" style="display:inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                title="{{ $product->is_active ? 'Desactivar' : 'Activar' }}"
                                style="padding:4px 8px;background:{{ $product->is_active ? 'rgba(245,158,11,.1)' : 'rgba(16,185,129,.1)' }};color:{{ $product->is_active ? 'var(--gacov-warning)' : 'var(--gacov-success)' }};border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer">
                                {{ $product->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        @endcan
                        @can('products.delete')
                        <form method="POST" action="{{ route('products.destroy', $product) }}" style="display:inline"
                              onsubmit="return confirm('¿Desactivar el producto «{{ $product->name }}»?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                style="padding:4px 8px;background:rgba(239,68,68,.1);color:var(--gacov-error);border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer">
                                Eliminar
                            </button>
                        </form>
                        @endcan
                    </div>
                </td>
                @endcanany
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-12) 0;color:var(--gacov-text-muted)">
        <svg viewBox="0 0 20 20" fill="currentColor" width="40" height="40" style="margin:0 auto var(--space-4);opacity:.3"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
        <p style="font-size:15px;font-weight:500;margin-bottom:var(--space-2)">Sin productos activos</p>
        <p style="font-size:13px">No se encontraron productos activos con los filtros actuales.</p>
    </div>
    @endif
</div>

<div class="panel">
    <div class="panel-header">
        <span class="panel-title">Productos inactivos / obsoletos</span>
        <span class="badge badge-neutral">{{ $inactiveProducts->count() }}</span>
    </div>
    @if($inactiveProducts->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código / SKU</th>
                <th>WO</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th style="text-align:right">Precio venta</th>
                <th>Estado</th>
                @canany(['products.edit', 'products.delete'])
                <th>Acciones</th>
                @endcanany
            </tr>
        </thead>
        <tbody>
            @foreach($inactiveProducts as $product)
            <tr>
                <td><strong>#{{ $product->id }}</strong></td>
                <td><code style="font-size:12px;color:var(--gacov-text-muted)">{{ $product->code }}</code></td>
                <td>{{ $product->worldoffice_code ?: '—' }}</td>
                <td><strong>{{ $product->name }}</strong></td>
                <td>{{ str_replace('_', ' ', $product->category) }}</td>
                <td style="color:var(--gacov-text-secondary)">{{ $product->unit_of_measure }}</td>
                <td style="text-align:right;font-weight:600">${{ number_format((float)$product->unit_price, 0, ',', '.') }}</td>
                <td><span class="badge badge-neutral">Inactivo / obsoleto</span></td>
                @canany(['products.edit', 'products.delete'])
                <td>
                    <div style="display:flex;gap:var(--space-2);align-items:center">
                        @can('products.edit')
                        <a href="{{ route('products.edit', $product) }}"
                           style="padding:4px 8px;background:rgba(0,212,255,.1);color:var(--gacov-primary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('products.toggle', $product) }}" style="display:inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                style="padding:4px 8px;background:rgba(16,185,129,.1);color:var(--gacov-success);border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer">
                                Reactivar
                            </button>
                        </form>
                        @endcan
                    </div>
                </td>
                @endcanany
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-10) 0;color:var(--gacov-text-muted)">
        No hay productos inactivos u obsoletos con los filtros actuales.
    </div>
    @endif
</div>
@endsection
