<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Inversiones GACOV S.A.S.</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#00D4FF">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
@php
    $routeName = request()->route()?->getName() ?? '';
    $isDriverRoute = str_starts_with($routeName, 'driver.');
    $driverRouteQuery = request()->filled('route_id') ? ['route_id' => request('route_id')] : [];
@endphp
<body class="{{ $isDriverRoute ? 'driver-mobile-active' : '' }}">

<div class="gacov-layout" id="app-layout">

    {{-- ── Sidebar ─────────────────────────────────────────────── --}}
    <aside class="gacov-sidebar" id="sidebar">

        {{-- Logo --}}
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <svg viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="url(#sg)"/><path d="M9 16a7 7 0 0 1 7-7c1.93 0 3.68.77 4.95 2.02H18a1.5 1.5 0 0 0 0 3h5v-5a1.5 1.5 0 0 0-3 0v.55A10 10 0 1 0 9.02 17L9 16z" fill="#fff" opacity=".95"/><defs><linearGradient id="sg" x1="0" y1="0" x2="32" y2="32"><stop offset="0%" stop-color="#00D4FF"/><stop offset="100%" stop-color="#7C3AED"/></linearGradient></defs></svg>
            </div>
            <div class="sidebar-brand-text">
                <span class="brand-main">GACOV</span>
                <span class="brand-sub">Inventarios</span>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="sidebar-nav">

            @php $route = $routeName; @endphp

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" class="nav-item {{ str_starts_with($route,'dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/></svg>
                <span>Dashboard</span>
            </a>

            {{-- Inventario --}}
            <div class="nav-section">Inventario</div>

            <a href="{{ route('inventory.warehouse') }}" class="nav-item {{ str_starts_with($route,'inventory') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
                <span>Bodega Principal</span>
            </a>

            <a href="{{ route('products.index') }}" class="nav-item {{ str_starts_with($route,'products') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
                <span>Productos</span>
            </a>

            {{-- Rutas y Máquinas --}}
            <div class="nav-section">Operaciones</div>

            <a href="{{ route('transfers.index') }}" class="nav-item {{ str_starts_with($route,'transfers') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/></svg>
                <span>Traslados</span>
            </a>

            <a href="{{ route('driver.stocking.create') }}" class="nav-item {{ str_starts_with($route,'driver.stocking') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>
                <span>Surtido Máquinas</span>
            </a>

            <a href="{{ route('machines.index') }}" class="nav-item {{ str_starts_with($route,'machines') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                <span>Máquinas</span>
            </a>

            <a href="{{ route('driver.sales.create') }}" class="nav-item {{ str_starts_with($route,'driver.sales') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                <span>Ventas Máquinas</span>
            </a>

            {{-- Reportes --}}
            <div class="nav-section">Reportes</div>

            <a href="#" class="nav-item {{ str_starts_with($route,'worldoffice') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                <span>Exportar WorldOffice</span>
            </a>

            <a href="{{ route('inventory.movements') }}" class="nav-item {{ str_starts_with($route,'inventory.movements') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"/></svg>
                <span>Reportes</span>
            </a>

            {{-- Admin --}}
            @canany(['users.view','roles.manage','system.modules'])
            <div class="nav-section">Administración</div>

            @can('users.view')
            <a href="{{ route('admin.users.index') }}" class="nav-item {{ str_starts_with($route,'admin.users') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                <span>Usuarios</span>
            </a>
            @endcan

            @can('roles.manage')
            <a href="#" class="nav-item {{ str_starts_with($route,'admin.roles') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>Roles y Permisos</span>
            </a>
            @endcan

            @can('system.modules')
            <a href="#" class="nav-item {{ str_starts_with($route,'admin.modules') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/></svg>
                <span>Módulos</span>
            </a>
            @endcan
            @endcanany

        </nav>

        {{-- User info + logout --}}
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ auth()->user()->getRoleNames()->first() ?? '—' }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout" title="Cerrar sesión">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </button>
            </form>
        </div>

    </aside>

    {{-- ── Main ────────────────────────────────────────────────── --}}
    <main class="gacov-main">

        {{-- Topbar --}}
        <header class="gacov-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menú">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
            </button>

            <div class="topbar-breadcrumb">
                <span class="topbar-company">Inversiones GACOV S.A.S.</span>
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" style="color:var(--gacov-text-muted)"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                <span class="topbar-page">@yield('title', 'Dashboard')</span>
            </div>

            <div class="topbar-actions">
                {{-- Badge conexión PWA --}}
                <div id="connection-badge" class="connection-badge online" data-offline-enabled>
                    <span class="badge-dot"></span> En línea
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="alert alert-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l3-3z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-error">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
        @endif

        {{-- Contenido de la página --}}
        <div class="gacov-content">
            @yield('content')
        </div>

        {{-- Footer --}}
        <footer class="gacov-footer">
            Inversiones GACOV S.A.S. &copy; {{ date('Y') }}
            &nbsp;·&nbsp; Desarrollado por <strong>AMR Tech</strong>
        </footer>

    </main>
</div>

@if($isDriverRoute)
<nav class="driver-mobile-nav" aria-label="Navegación conductor">
    <a href="{{ route('driver.dashboard', $driverRouteQuery) }}" class="driver-mobile-nav__item {{ str_starts_with($routeName, 'driver.dashboard') ? 'active' : '' }}">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/></svg>
        <span>Ruta</span>
    </a>
    <a href="{{ route('driver.stocking.create', $driverRouteQuery) }}" class="driver-mobile-nav__item {{ str_starts_with($routeName, 'driver.stocking') ? 'active' : '' }}">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg>
        <span>Surtido</span>
    </a>
    <a href="{{ route('driver.sales.create', $driverRouteQuery) }}" class="driver-mobile-nav__item {{ str_starts_with($routeName, 'driver.sales') ? 'active' : '' }}">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
        <span>Ventas</span>
    </a>
    <a href="{{ route('driver.inventory', $driverRouteQuery) }}" class="driver-mobile-nav__item {{ str_starts_with($routeName, 'driver.inventory') ? 'active' : '' }}">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
        <span>Vehículo</span>
    </a>
</nav>
@endif

<script>
// Toggle sidebar en móvil
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('app-layout').classList.toggle('sidebar-open');
});
</script>
@livewireScripts
@stack('scripts')
</body>
</html>
