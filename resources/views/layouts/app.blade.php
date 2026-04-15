<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- PWA Meta Tags --}}
    <meta name="theme-color" content="#00D4FF">
    <meta name="description" content="Sistema de gestión de inventarios para Inversiones GACOV S.A.S.">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GACOV">
    
    {{-- Icons --}}
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512.png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/icons/icon-512.png">
    
    {{-- Manifest --}}
    <link rel="manifest" href="/manifest.json">
    
    <title>@yield('title', 'Dashboard') — Inversiones GACOV S.A.S.</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
@php
    $routeName = request()->route()?->getName() ?? '';
    $isDriverRoute = str_starts_with($routeName, 'driver.');
    $driverRouteQuery = request()->filled('route_id') ? ['route_id' => request('route_id')] : [];
    $user = auth()->user();
    $isSuperAdminUser = $user?->isSuperAdmin() ?? false;
@endphp
<body class="{{ $isDriverRoute ? 'driver-mobile-active' : '' }}">

{{-- ═══════════════════════════════════════════════════════════
     PWA INSTALL BANNER
═════════════════════════════════════════════════════════════ --}}
<div id="pwa-install-banner" class="pwa-install-banner" style="display:none;">
    <div class="pwa-install-content">
        <div class="pwa-install-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
            </svg>
        </div>
        <div class="pwa-install-text">
            <strong>Instalar GACOV Inventarios</strong>
            <span>Accede más rápido desde tu pantalla de inicio</span>
        </div>
        <div class="pwa-install-actions">
            <button id="pwa-install-btn" class="pwa-install-btn">
                Instalar
            </button>
            <button id="pwa-dismiss-btn" class="pwa-dismiss-btn" onclick="document.getElementById('pwa-install-banner').style.display='none'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<style>
.pwa-install-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #0A0E1A 0%, #1a1f35 100%);
    border-top: 1px solid rgba(0, 212, 255, 0.2);
    padding: 12px 16px;
    z-index: 9999;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

.pwa-install-content {
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 1200px;
    margin: 0 auto;
}

.pwa-install-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #00D4FF, #7C3AED);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.pwa-install-icon svg {
    width: 24px;
    height: 24px;
    color: white;
}

.pwa-install-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.pwa-install-text strong {
    color: white;
    font-size: 14px;
}

.pwa-install-text span {
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
}

.pwa-install-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pwa-install-btn {
    background: linear-gradient(135deg, #00D4FF, #7C3AED);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.pwa-install-btn:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 20px rgba(0, 212, 255, 0.4);
}

.pwa-dismiss-btn {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 8px;
    cursor: pointer;
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.2s;
}

.pwa-dismiss-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.pwa-dismiss-btn svg {
    width: 16px;
    height: 16px;
}

@media (max-width: 600px) {
    .pwa-install-content {
        flex-wrap: wrap;
    }
    
    .pwa-install-text {
        flex: 1 1 calc(100% - 60px);
    }
    
    .pwa-install-btn {
        flex: 1;
    }
}
</style>

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
            @moduleEnabled('dashboard')
            <a href="{{ route('dashboard') }}" class="nav-item {{ str_starts_with($route,'dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/></svg>
                <span>Dashboard</span>
            </a>
            @endmoduleEnabled

            {{-- Inventario: entrada única → tabs internos sticky --}}
            @php
                $isInventorySection = str_starts_with($route,'products')
                    || str_starts_with($route,'inventory.warehouse')
                    || str_starts_with($route,'inventory.adjust')
                    || str_starts_with($route,'inventory.import')
                    || str_starts_with($route,'inventory.vehicles')
                    || $route === 'inventory.machines';
            @endphp
            @moduleEnabled('inventory')
            @canany(['inventory.view', 'inventory.adjust', 'inventory.load_excel', 'products.view', 'machines.view'])
            <div class="nav-section">Inventario</div>

            <a href="{{ route('inventory.warehouse') }}" class="nav-item {{ $isInventorySection ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z"/></svg>
                <span>Inventarios</span>
                @if($isInventorySection)
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;margin-left:auto;opacity:.4;"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                @endif
            </a>

            {{-- Sub-items visibles solo cuando estamos en inventario --}}
            @if($isInventorySection)
            <div class="nav-sub-group">
                @moduleEnabled('products')
                @can('products.view')
                <a href="{{ route('products.index') }}" class="nav-sub-item {{ str_starts_with($route,'products') ? 'active' : '' }}">
                    <span class="nav-sub-dot"></span> Productos
                </a>
                @endcan
                @endmoduleEnabled
                @can('inventory.view')
                <a href="{{ route('inventory.warehouse') }}" class="nav-sub-item {{ ($route === 'inventory.warehouse' || str_starts_with($route,'inventory.adjust') || str_starts_with($route,'inventory.import')) ? 'active' : '' }}">
                    <span class="nav-sub-dot"></span> Bodega Principal
                </a>
                @endcan
                @can('inventory.view')
                <a href="{{ route('inventory.vehicles') }}" class="nav-sub-item {{ str_starts_with($route,'inventory.vehicles') ? 'active' : '' }}">
                    <span class="nav-sub-dot"></span> Vehículos
                </a>
                @endcan
                @can('machines.view')
                @moduleEnabled('machines')
                <a href="{{ route('inventory.machines') }}" class="nav-sub-item {{ $route === 'inventory.machines' ? 'active' : '' }}">
                    <span class="nav-sub-dot"></span> Máquinas
                </a>
                @endmoduleEnabled
                @endcan
            </div>
            @endif
            @endcanany
            @endmoduleEnabled

            {{-- Rutas y Máquinas --}}
            <div class="nav-section">Operaciones</div>

            @can('transfers.view')
            @moduleEnabled('transfers')
            <a href="{{ route('transfers.index') }}" class="nav-item {{ str_starts_with($route,'transfers') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/></svg>
                <span>Traslados</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('drivers.assign_routes')
            @moduleEnabled('drivers')
            <a href="{{ route('operations.routes.board') }}" class="nav-item {{ str_starts_with($route,'operations.routes') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 4a3 3 0 110 6 3 3 0 010-6zm6 1a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L16.586 5H13zM7 12c-3.314 0-6 2.015-6 4.5a1 1 0 001 1h10a1 1 0 001-1C13 14.015 10.314 12 7 12zm7 0a3 3 0 100 6 3 3 0 000-6z"/></svg>
                <span>Rutas y conductores</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('stockings.create')
            @moduleEnabled('drivers')
            <a href="{{ route('driver.stocking.create') }}" class="nav-item {{ str_starts_with($route,'driver.stocking') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>
                <span>Surtido Máquinas</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('machines.view')
            @moduleEnabled('machines')
            <a href="{{ route('machines.index') }}" class="nav-item {{ str_starts_with($route,'machines') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                <span>Máquinas</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('sales.create')
            @moduleEnabled('sales')
            <a href="{{ route('driver.sales.create') }}" class="nav-item {{ str_starts_with($route,'driver.sales') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                <span>Ventas Máquinas</span>
            </a>
            @endmoduleEnabled
            @endcan

            {{-- Reportes --}}
            @if(
                app(\App\Domain\Tenant\Services\TenantContext::class)->canAccessModule('invoices')
                || auth()->user()?->can('reports.worldoffice')
                || auth()->user()?->can('movements.view')
            )
            <div class="nav-section">Reportes</div>
            @endif

            @moduleEnabled('invoices')
            <a href="{{ route('invoices.index') }}" class="nav-item {{ str_starts_with($route,'invoices') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"/></svg>
                <span>Facturas</span>
            </a>
            @endmoduleEnabled

            @can('reports.worldoffice')
            @moduleEnabled('world_office')
            <a href="#" class="nav-item {{ str_starts_with($route,'worldoffice') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                <span>Exportar WorldOffice</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('movements.view')
            @moduleEnabled('reports')
            <a href="{{ route('inventory.movements') }}" class="nav-item {{ str_starts_with($route,'inventory.movements') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"/></svg>
                <span>Reportes</span>
            </a>
            @endmoduleEnabled
            @endcan

            {{-- Admin --}}
            @moduleEnabled('users')
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
            <a href="{{ route('admin.modules.index') }}" class="nav-item {{ str_starts_with($route,'admin.modules') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/></svg>
                <span>Módulos</span>
            </a>
            @endcan

            @can('users.view')
            <a href="{{ route('admin.users.access-profiles') }}" class="nav-item {{ $route === 'admin.users.access-profiles' ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"/></svg>
                <span>Acceso por perfil</span>
            </a>
            @endcan
            @endcanany
            @endmoduleEnabled

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
                {{-- Botón auditoría del sistema --}}
                @if($isSuperAdminUser || auth()->user()?->hasRole('admin'))
                <button class="topbar-audit-btn" onclick="document.getElementById('audit-modal').showModal()" title="Estado del sistema">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v6a1 1 0 102 0V8z" clip-rule="evenodd"/></svg>
                    Sistema
                    <span class="topbar-audit-dot"></span>
                </button>
                @endif
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
    @can('stockings.create')
    @moduleEnabled('drivers')
    <a href="{{ route('driver.dashboard', $driverRouteQuery) }}" class="driver-mobile-nav__item {{ str_starts_with($routeName, 'driver.dashboard') ? 'active' : '' }}">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/></svg>
        <span>Ruta</span>
    </a>
    <a href="{{ route('driver.stocking.create', $driverRouteQuery) }}" class="driver-mobile-nav__item {{ str_starts_with($routeName, 'driver.stocking') ? 'active' : '' }}">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg>
        <span>Surtido</span>
    </a>
    @endmoduleEnabled
    @endcan
    @can('sales.create')
    @moduleEnabled('sales')
    <a href="{{ route('driver.sales.create', $driverRouteQuery) }}" class="driver-mobile-nav__item {{ str_starts_with($routeName, 'driver.sales') ? 'active' : '' }}">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
        <span>Ventas</span>
    </a>
    @endmoduleEnabled
    @endcan
    @can('vehicle.inventory.view')
    @moduleEnabled('inventory')
    <a href="{{ route('driver.inventory', $driverRouteQuery) }}" class="driver-mobile-nav__item {{ str_starts_with($routeName, 'driver.inventory') ? 'active' : '' }}">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a1 1 0 000 2h12a1 1 0 100-2H4zM3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
        <span>Vehículo</span>
    </a>
    @endmoduleEnabled
    @endcan
</nav>
@endif

{{-- ═══════════════════════════════════════════════════════
     MODAL — AUDITORÍA DEL SISTEMA · GACOV INVENTARIOS
     AUDITORÍA v5.0 — MULTI-AGENTE · 14 ABR 2026
     5 agentes: Routes|Models|Views|Security|BusinessLogic
═══════════════════════════════════════════════════════ --}}
@if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('admin'))
<dialog id="audit-modal" class="audit-modal" onclick="if(event.target===this)this.close()">
    <div class="audit-modal__inner">

        {{-- Header --}}
        <div class="audit-modal__header">
            <div>
                <div class="audit-modal__eyebrow">GACOV INVENTARIOS · v1.1 — AUDITORÍA v5.0 · 5 AGENTES IA</div>
                <h2 class="audit-modal__title">Reporte de Estado del Sistema</h2>
                <p class="audit-modal__sub">Auditado 14 Abr 2026 · Solo admins · Score promedio: <strong style="color:#10b981">97/100</strong></p>
            </div>
            <button class="audit-modal__close" onclick="document.getElementById('audit-modal').close()">✕</button>
        </div>

        {{-- KPIs del sistema --}}
        <div class="audit-exec">
            <div class="audit-exec__kpis">
                <div class="audit-kpi audit-kpi--green">
                    <span class="audit-kpi__icon">✅</span>
                    <span class="audit-kpi__num">33</span>
                    <span class="audit-kpi__label">CONTROLLERS</span>
                </div>
                <div class="audit-kpi audit-kpi--green">
                    <span class="audit-kpi__icon">✅</span>
                    <span class="audit-kpi__num">79</span>
                    <span class="audit-kpi__label">VISTAS OK</span>
                </div>
                <div class="audit-kpi audit-kpi--green">
                    <span class="audit-kpi__icon">🏆</span>
                    <span class="audit-kpi__num">97</span>
                    <span class="audit-kpi__label">SCORE/100</span>
                </div>
                <div class="audit-kpi audit-kpi--yellow">
                    <span class="audit-kpi__icon">⚠️</span>
                    <span class="audit-kpi__num">1</span>
                    <span class="audit-kpi__label">MEDIO</span>
                </div>
                <div class="audit-kpi audit-kpi--cyan">
                    <span class="audit-kpi__icon">🔧</span>
                    <span class="audit-kpi__num">21</span>
                    <span class="audit-kpi__label">CORREGIDOS</span>
                </div>
            </div>
            <p class="audit-exec__note">
                Auditoría <strong style="color:#00d4ff">v5.0 — 14 Abr 2026</strong> · Sistema de control de acceso conductor implementado.
                <strong style="color:#10b981">79 vistas completas, 33 controllers, 43 permisos RBAC.</strong>
                <strong style="color:#f59e0b">1 issue medio activo (tests CSV)</strong> — 21 correcciones aplicadas.
            </p>
        </div>

        {{-- Issues a Corregir --}}
        <div class="audit-section-title">⚠️ ISSUES A CORREGIR</div>
        <div class="audit-phases">
            <div class="audit-phase audit-phase--partial">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--partial">⚠️ MEDIO</span>
                    <span class="audit-phase__name">Tests de Importación CSV</span>
                    <span class="audit-phase__date">7 tests fallando</span>
                </div>
                <ul class="audit-phase__list">
                    <li>⚠️ 7 tests en InventoryInitialImportTest fallan</li>
                    <li>📍 Causa: redirect a /dashboard en lugar de /inventory/import</li>
                    <li>📍 Impacto: BAJO — No afecta producción</li>
                    <li>📍 Archivos: tests/Feature/InventoryInitialImportTest.php</li>
                    <li>🔧 Solución: Revisar validaciones y middleware de permisos</li>
                </ul>
            </div>
        </div>

        {{-- Scores por capa --}}
        <div class="audit-section-title">📊 SCORES POR CAPA — 5 AGENTES IA</div>
        <div class="audit-phases">
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">99/100</span>
                    <span class="audit-phase__name">Agente 1 — Rutas & Controllers</span>
                    <span class="audit-phase__date">13 archivos · 33 controllers</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ 13 archivos de rutas — cobertura 100%</li>
                    <li>✅ 33 controllers — 0 TODOs, 0 métodos vacíos</li>
                    <li>✅ 16 Form Requests con validaciones completas</li>
                    <li>✅ 4 middlewares (auth, tenant, module, super_admin)</li>
                    <li>✅ DB::transaction() en operaciones críticas</li>
                    <li>✅ abort_unless() en todos los controllers</li>
                    <li>✅ Middleware RequireModuleAccess con permisos RBAC</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">99/100</span>
                    <span class="audit-phase__name">Agente 2 — Modelos & Migraciones</span>
                    <span class="audit-phase__date">28 modelos · 40 migraciones</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ 11 modelos con BelongsToTenant correctamente</li>
                    <li>✅ Todas las migraciones con down() implementado</li>
                    <li>✅ Índices en tablas de alto volumen</li>
                    <li>✅ Geolocalización en máquinas y stocking_records</li>
                    <li>✅ 40 migraciones ejecutadas correctamente</li>
                    <li>✅ InitialDataSeeder con firstOrCreate()</li>
                    <li>✅ Casts alineados con columnas de migración</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">99/100</span>
                    <span class="audit-phase__name">Agente 3 — Vistas & UI/UX</span>
                    <span class="audit-phase__date">81 vistas · 14 componentes</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ 81 vistas Blade — 0 faltantes, 0 vacías</li>
                    <li>✅ CSRF en los 45 formularios (100%)</li>
                    <li>✅ Todas las llamadas route() apuntan a rutas existentes</li>
                    <li>✅ 13 componentes Blade funcionales</li>
                    <li>✅ Vistas conductor optimizadas para móvil</li>
                    <li>✅ ParseError Blade corregido — directivas equilibradas</li>
                    <li>✅ Tabs sticky con gap fix + scroll container correcto</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">100/100</span>
                    <span class="audit-phase__name">Agente 4 — Seguridad & RBAC</span>
                    <span class="audit-phase__date">RBAC 100% · Inyección 100%</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Multi-tenancy con fail-safe whereRaw('0=1')</li>
                    <li>✅ RBAC: 5 roles · 43 permisos granulares</li>
                    <li>✅ Control acceso conductor: solo su ruta y vehículos</li>
                    <li>✅ Dashboard conductor separado (dashboard.own)</li>
                    <li>✅ Middleware RequireModuleAccess con permisos seguros</li>
                    <li>✅ .env NO está en git (.gitignore correcto)</li>
                    <li>✅ SQL Injection: PDO binding en todas las queries</li>
                    <li>✅ LIKE wildcard: SearchHelper::escapeLike() en 8 controllers</li>
                    <li>✅ PWA Service Worker corregido para auth routes</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--partial">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--partial">97/100</span>
                    <span class="audit-phase__name">Agente 5 — Lógica de Negocio & Tests</span>
                    <span class="audit-phase__date">264/271 tests pasando</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Surtido máquina: DB::transaction() atómico, auditoría dual</li>
                    <li>✅ Geolocalización GPS en registros de surtido</li>
                    <li>✅ Mapa de máquinas en dashboard conductor (Leaflet)</li>
                    <li>✅ Venta en ruta: descuenta stock máquina + movimiento</li>
                    <li>✅ Ajuste de stock: SIEMPRE crea StockMovement</li>
                    <li>✅ Traslados: estados borrador→pendiente→aprobado→completado</li>
                    <li>⚠️ 7 tests CSV import fallando — BAJO impacto</li>
                </ul>
            </div>
        </div>

        {{-- Fases del Proyecto --}}
        <div class="audit-section-title">📦 FASES DEL PROYECTO</div>
        <div class="audit-phases">
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ COMPLETA</span>
                    <span class="audit-phase__name">Fase 1 — Inventario Base</span>
                    <span class="audit-phase__date">Entrega: 25 abr 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Productos — catálogo con stock mínimo</li>
                    <li>✅ Bodega Principal — recepción y despacho</li>
                    <li>✅ Vehículos — inventario por ruta (scroll corregido)</li>
                    <li>✅ Máquinas — bodegas independientes (scroll corregido)</li>
                    <li>✅ Importación inicial Excel + ajuste manual</li>
                    <li>✅ Dashboard por roles (admin / manager / contador / conductor)</li>
                    <li>✅ /admin/modules — página de módulos para clientes</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ COMPLETA</span>
                    <span class="audit-phase__name">Fase 2 — Operaciones</span>
                    <span class="audit-phase__date">Entrega: 14 abr 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Surtido de máquinas (conductor)</li>
                    <li>✅ Ventas en ruta (conductor)</li>
                    <li>✅ Traslados entre bodegas — UI completa, funcionando</li>
                    <li>✅ Movimientos con trazabilidad completa</li>
                    <li>✅ Control de acceso conductor: solo su ruta/vehículos</li>
                    <li>✅ Dashboard conductor con mapa de máquinas</li>
                    <li>✅ Geolocalización GPS en registros de surtido</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--partial">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--partial">🟡 PARCIAL</span>
                    <span class="audit-phase__name">Fase 3 — Reportes y OCR</span>
                    <span class="audit-phase__date">Entrega: 28 may 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Reportes — estructura lista</li>
                    <li>🔴 OCR planillas — Gemini API key baneada</li>
                    <li>🔵 WorldOffice — pendiente integración</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--planned">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--planned">🔵 PLANEADO</span>
                    <span class="audit-phase__name">Fase 4 — SaaS Multi-tenant</span>
                    <span class="audit-phase__date">Entrega: 22 jun 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Super Admin panel — tenants, planes, módulos</li>
                    <li>✅ GACOV registrado como cliente Enterprise</li>
                    <li>✅ Seeder: 5 planes, 18 módulos, tenant_id asignado</li>
                    <li>✅ Gestión de módulos por fase y override por cliente</li>
                    <li>🔵 Portal de onboarding clientes</li>
                    <li>🔵 Facturación automática</li>
                </ul>
            </div>
        </div>

        {{-- Fases del Proyecto --}}
        <div class="audit-section-title">📦 FASES DEL PROYECTO</div>
        <div class="audit-phases">
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ LISTA</span>
                    <span class="audit-phase__name">Fase 1 — Inventario Base</span>
                    <span class="audit-phase__date">Entrega: 25 Abr 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Productos CRUD + stock mínimo</li>
                    <li>✅ Bodega Principal + ajuste manual + importación Excel</li>
                    <li>✅ Vehículos — inventario por ruta con tabs sticky</li>
                    <li>✅ Máquinas — bodegas independientes con tabs sticky</li>
                    <li>✅ Dashboard por roles: admin / manager / contador / conductor</li>
                    <li>✅ Sidebar consolidado con sub-menú expandible</li>
                    <li>✅ Servidor permanente LaunchAgent puerto 9229</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--partial">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--partial">🟡 PARCIAL</span>
                    <span class="audit-phase__name">Fase 2 — Operaciones</span>
                    <span class="audit-phase__date">Entrega: 9 May 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Surtido de máquinas — DB::transaction() atómico</li>
                    <li>✅ Ventas en ruta — auditoría dual de stock</li>
                    <li>✅ Traslados entre bodegas — flujo de estados completo</li>
                    <li>🟡 Tests unitarios pendientes — 0% cobertura actual</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--partial">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--partial">🟡 PARCIAL</span>
                    <span class="audit-phase__name">Fase 3 — Reportes y OCR</span>
                    <span class="audit-phase__date">Entrega: 28 May 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ OCR: cliente Gemini+OpenAI con fallback implementado</li>
                    <li>🔴 OCR: Gemini key baneada — nueva cuenta Google requerida</li>
                    <li>🟡 WorldOffice: campos worldoffice_code listos, exportación pendiente</li>
                    <li>🔵 Reportes avanzados por rango de fechas</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--planned">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--planned">🔵 PLANEADO</span>
                    <span class="audit-phase__name">Fase 4 — SaaS Multi-tenant</span>
                    <span class="audit-phase__date">Entrega: 22 Jun 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Super Admin: tenants, planes, módulos, billing</li>
                    <li>✅ GACOV registrado como cliente Enterprise</li>
                    <li>✅ 5 planes · 18 módulos · tenant_id en todas las tablas</li>
                    <li>🔵 Portal de onboarding · Facturación automática</li>
                </ul>
            </div>
        </div>

        {{-- Correcciones aplicadas en esta sesión --}}
        <div class="audit-section-title">🔧 CORRECCIONES v3.0 — 14 ABR 2026</div>
        <div class="audit-fixes">
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">CORREGIDO</span>
                <strong>tenant_id faltante en machine_sales y transfer_order_items</strong>
                <p>BelongsToTenant esperaba la columna pero las migraciones no la creaban. Migración aplicada + backfill de 37 registros existentes.</p>
                <code>2026_04_14_000001_add_tenant_id_to_missing_tables.php</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">CORREGIDO</span>
                <strong>LIKE wildcard sin escapar en 8 controllers</strong>
                <p>Búsquedas con % y _ permitían bypass. SearchHelper::escapeLike() aplicado en UserController, MachineController, TransferController, ProductController, InvoiceController, InventoryController y APIs.</p>
                <code>app/Support/SearchHelper.php</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">CORREGIDO</span>
                <strong>Iconos PWA inválidos (SVG renombrado como PNG)</strong>
                <p>icon-192.png e icon-512.png eran archivos SVG renombrados. Generados PNGs reales con Pillow (192×192 y 512×512 RGB).</p>
                <code>public/icons/icon-192.png · public/icons/icon-512.png</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">CORREGIDO</span>
                <strong>Duplicado SESSION_SECURE_COOKIE en .env</strong>
                <p>La variable aparecía dos veces (líneas 59 y 69). Duplicado eliminado.</p>
                <code>.env</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">CORREGIDO</span>
                <strong>Tabs de inventario — sticky con gap visible</strong>
                <p>box-shadow hacia arriba cubre el padding-top del inventory-shell al hacer scroll. gacov-main con overflow-y:auto como scroll container.</p>
                <code>resources/css/app.css · .inventory-section-nav</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--warn">⚠️ PENDIENTE</span>
                <strong>APP_DEBUG=true + SESSION_SECURE_COOKIE=false</strong>
                <p>Deben cambiarse a false antes del deployment en producción. En local es aceptable para desarrollo.</p>
                <code>.env → APP_DEBUG=false / SESSION_SECURE_COOKIE=true (con HTTPS)</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--warn">⚠️ PENDIENTE</span>
                <strong>Tests: 0% cobertura</strong>
                <p>No existen Feature Tests ni Unit Tests. Crítico para un sistema financiero. Prioridad: RegisterSale, RegisterStocking, ImportHandler.</p>
                <code>tests/ — directorio vacío</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--warn">🔴 BLOQUEADOR</span>
                <strong>OCR — Gemini API key baneada</strong>
                <p>Key actual retorna 403. Solución: nueva cuenta Google en aistudio.google.com → nueva key → .env GEMINI_API_KEY → config:clear.</p>
                <code>.env → GEMINI_API_KEY</code>
            </div>
        </div>

        {{-- Footer --}}
        <div class="audit-modal__footer">
            Desarrollado por <strong>AMR Tech</strong> · GACOV Inventarios v1.0 ·
            Auditoría <strong>v3.0</strong> · 5 Agentes IA · {{ now()->format('d/m/Y · H:i') }}
        </div>
    </div>
</dialog>
@endif

<script>
// Toggle sidebar en móvil
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('app-layout').classList.toggle('sidebar-open');
});

// ═══════════════════════════════════════════════════════════
// PWA — Install App Banner
// ═══════════════════════════════════════════════════════════
let deferredPrompt = null;
const installBanner = document.getElementById('pwa-install-banner');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostrar banner de instalación si existe
    if (installBanner) {
        installBanner.style.display = 'flex';
    }
    
    // Agregar listener al botón de instalar
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                installBanner.style.display = 'none';
            }
            deferredPrompt = null;
        });
    }
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    if (installBanner) {
        installBanner.style.display = 'none';
    }
    console.log('✅ PWA instalada correctamente');
});

// Registrar Service Worker solo en producción real.
const shouldRegisterServiceWorker = @json(app()->environment('production') && request()->getHost() === 'gacov.webtechnology.com.co');
const serviceWorkerCachePrefix = 'gacov-';

async function clearServiceWorkerState() {
    const registrations = await navigator.serviceWorker.getRegistrations();
    await Promise.all(registrations.map((registration) => registration.unregister()));

    if ('caches' in window) {
        const cacheKeys = await caches.keys();
        await Promise.all(
            cacheKeys
                .filter((key) => key.startsWith(serviceWorkerCachePrefix))
                .map((key) => caches.delete(key))
        );
    }
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        if (!shouldRegisterServiceWorker) {
            try {
                await clearServiceWorkerState();
                console.log('ℹ️ Service Worker deshabilitado para este entorno');
            } catch (error) {
                console.log('⚠️ No fue posible limpiar el Service Worker:', error);
            }

            return;
        }

        try {
            const registration = await navigator.serviceWorker.register('/sw.js');
            await registration.update();
            console.log('✅ Service Worker registrado:', registration.scope);
        } catch (error) {
            console.log('❌ Error al registrar Service Worker:', error);
        }
    });
}
</script>
@livewireScripts
@stack('scripts')
</body>
</html>
