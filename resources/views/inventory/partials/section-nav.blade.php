@php
    $inventoryRoute = request()->route()?->getName() ?? '';
@endphp

<nav class="inventory-section-nav" aria-label="Secciones de inventario">
    @can('products.view')
    @moduleEnabled('products')
    <a href="{{ route('products.index') }}" class="inventory-section-nav__item {{ $inventoryRoute === 'products.index' || str_starts_with($inventoryRoute, 'products.') ? 'active' : '' }}" title="Productos · Catálogo general" aria-label="Productos">
        <span class="inventory-section-nav__icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4z" clip-rule="evenodd"/></svg>
        </span>
        <span class="inventory-section-nav__text">
            <span class="inventory-section-nav__label">Productos</span>
            <span class="inventory-section-nav__copy">Catalogo general</span>
        </span>
    </a>
    @endmoduleEnabled
    @endcan

    @can('inventory.view')
    @moduleEnabled('inventory')
    <a href="{{ route('inventory.warehouse') }}" class="inventory-section-nav__item {{ $inventoryRoute === 'inventory.warehouse' || str_starts_with($inventoryRoute, 'inventory.adjust') || str_starts_with($inventoryRoute, 'inventory.import') ? 'active' : '' }}" title="Bodega Principal · Recepción y despacho" aria-label="Bodega Principal">
        <span class="inventory-section-nav__icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
        </span>
        <span class="inventory-section-nav__text">
            <span class="inventory-section-nav__label">Bodega Principal</span>
            <span class="inventory-section-nav__copy">Recepcion y despacho</span>
        </span>
    </a>

    @can('inventory.load_excel')
    <a href="{{ route('inventory.purchases.index') }}" class="inventory-section-nav__item {{ str_starts_with($inventoryRoute, 'inventory.purchases') ? 'active' : '' }}" title="Compras CSV · Tabla temporal" aria-label="Compras CSV">
        <span class="inventory-section-nav__icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-1h-2v1H5V5h10v1h2V5a2 2 0 00-2-2H5z"/><path d="M12 7a1 1 0 011-1h3a1 1 0 011 1v3h-2V8h-2V7zm-2 5a1 1 0 011-1h6v2h-6a1 1 0 01-1-1zm6 2h2v2a1 1 0 01-1 1h-4a1 1 0 110-2h3v-1z"/></svg>
        </span>
        <span class="inventory-section-nav__text">
            <span class="inventory-section-nav__label">Compras CSV</span>
            <span class="inventory-section-nav__copy">Tabla temporal</span>
        </span>
    </a>
    @endcan

    <a href="{{ route('inventory.vehicles') }}" class="inventory-section-nav__item {{ $inventoryRoute === 'inventory.vehicles' || str_starts_with($inventoryRoute, 'inventory.vehicles.import') ? 'active' : '' }}" title="Vehículos · Rutas y carros" aria-label="Vehículos">
        <span class="inventory-section-nav__icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 11a1 1 0 011-1h9l2.447-2.04A1 1 0 0117 8.728V14a2 2 0 01-2 2h-.382a2.5 2.5 0 01-4.236 0H8.618a2.5 2.5 0 01-4.236 0H4a2 2 0 01-2-2v-3z"/></svg>
        </span>
        <span class="inventory-section-nav__text">
            <span class="inventory-section-nav__label">Vehiculos</span>
            <span class="inventory-section-nav__copy">Rutas y carros</span>
        </span>
    </a>
    @endmoduleEnabled
    @endcan

    @can('machines.view')
    @moduleEnabled('machines')
    <a href="{{ route('inventory.machines') }}" class="inventory-section-nav__item {{ $inventoryRoute === 'inventory.machines' ? 'active' : '' }}" title="Máquinas · Bodegas por máquina" aria-label="Máquinas">
        <span class="inventory-section-nav__icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h2a2 2 0 012 2v2h1V4a2 2 0 114 0v2h1a2 2 0 012 2v4a2 2 0 01-2 2h-1v2a2 2 0 11-4 0v-2h-1v2a2 2 0 11-4 0v-2H6a2 2 0 01-2-2V8a2 2 0 012-2h1V4zm2 2h8V4H6v2zm0 2v4h8V8H6z" clip-rule="evenodd"/></svg>
        </span>
        <span class="inventory-section-nav__text">
            <span class="inventory-section-nav__label">Maquinas</span>
            <span class="inventory-section-nav__copy">Bodegas por maquina</span>
        </span>
    </a>
    @endmoduleEnabled
    @endcan

    @canany(['cash.manage', 'cash.view'])
    @moduleEnabled('inventory')
    <a href="{{ route('inventory.cash.index') }}" class="inventory-section-nav__item {{ $inventoryRoute === 'inventory.cash.index' || str_starts_with($inventoryRoute, 'inventory.cash.') || str_starts_with($inventoryRoute, 'cash.') ? 'active' : '' }}" title="Dinero · Efectivo y monedas colombianas" aria-label="Dinero">
        <span class="inventory-section-nav__icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 5a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm2 1v8h10V6H5zm5 1.25a2.75 2.75 0 110 5.5 2.75 2.75 0 010-5.5zM6.5 7.5a.75.75 0 100 1.5.75.75 0 000-1.5zm7 3.75a.75.75 0 100 1.5.75.75 0 000-1.5z"/></svg>
        </span>
        <span class="inventory-section-nav__text">
            <span class="inventory-section-nav__label">Dinero</span>
            <span class="inventory-section-nav__copy">Efectivo y monedas CO</span>
        </span>
    </a>
    @endmoduleEnabled
    @endcanany
</nav>
