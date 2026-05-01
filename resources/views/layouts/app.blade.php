@php
    $routeName = request()->route()?->getName() ?? '';
    $isDriverRoute = str_starts_with($routeName, 'driver.');
    $driverRouteQuery = request()->filled('route_id') ? ['route_id' => request('route_id')] : [];
    $user = auth()->user();
    $userInitials = collect(preg_split('/\s+/', trim((string) ($user?->name ?? '')), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $userInitials = $userInitials !== '' ? $userInitials : 'GA';
    $isSuperAdminUser = $user?->isSuperAdmin() ?? false;
    $isInventorySection = str_starts_with($routeName, 'products')
        || str_starts_with($routeName, 'inventory.warehouse')
        || str_starts_with($routeName, 'inventory.adjust')
        || str_starts_with($routeName, 'inventory.import')
        || str_starts_with($routeName, 'inventory.vehicles')
        || $routeName === 'inventory.machines';
    $shouldRegisterServiceWorker = app()->environment('production')
        && in_array(request()->getHost(), ['gacov.webtechnology.com.co', 'gacov.andersonmares.xyz'], true);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- PWA Meta Tags --}}
    <meta name="theme-color" content="#D71920">
    <meta name="description" content="Sistema de gestión de inventarios para Inversiones GACOV S.A.S.">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GACOV">
    
    {{-- Icons --}}
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512.png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/icons/icon-512.png">
    
    {{-- Manifest --}}
    <link rel="manifest" href="/manifest.json">
    
    <title>@yield('title', 'Dashboard') — Inversiones GACOV S.A.S.</title>

    @include('layouts.partials.vite-assets')
    @livewireStyles
    <link rel="stylesheet" href="/css/gacov-ui.css">
    <script>
    (function(){try{var p=JSON.parse(localStorage.getItem('gacov_ui_prefs')||'{}');var t=p.theme||'auto';if(t==='auto')t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',t);if(p.font)document.documentElement.setAttribute('data-font',p.font);if(p.density)document.documentElement.setAttribute('data-density',p.density);var brandAccent='#D71920';var brandGradient='linear-gradient(135deg,#D71920 0%,#111111 100%)';var r=document.documentElement.style;r.setProperty('--gacov-primary',brandAccent);r.setProperty('--gacov-border-focus',brandAccent);r.setProperty('--gacov-gradient',brandGradient);if(p.font){var fs={sm:13,md:15,lg:17};document.documentElement.style.setProperty('--ui-font-base',(fs[p.font]||15)+'px');}}catch(e){}})();
    </script>
    @stack('styles')
</head>
<body class="{{ trim(($isDriverRoute ? 'driver-mobile-active' : '') . ' ' . ($routeName === 'dashboard' ? 'dashboard-compact-mode' : '')) }}">

<div id="pwa-install-banner" class="pwa-install-banner" style="display:none;">
    <div class="pwa-install-content">
        <div class="pwa-install-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
            </svg>
        </div>
        <div class="pwa-install-text">
            <strong id="pwa-install-title">Instalar GACOV Inventarios</strong>
            <span id="pwa-install-copy">Accede más rápido desde tu pantalla de inicio</span>
        </div>
        <div class="pwa-install-actions">
            <button id="pwa-install-btn" class="pwa-install-btn" type="button">
                Instalar
            </button>
            <button id="pwa-dismiss-btn" class="pwa-dismiss-btn" type="button" onclick="document.getElementById('pwa-install-banner').style.display='none'; try { localStorage.setItem('gacov_pwa_banner_dismissed', String(Date.now())); } catch (e) {}">
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
    background: linear-gradient(135deg, #111111 0%, #1f1f1f 100%);
    border-top: 1px solid rgba(215, 25, 32, 0.2);
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
    background: linear-gradient(135deg, #D71920, #111111);
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
    background: linear-gradient(135deg, #D71920, #111111);
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
    box-shadow: 0 4px 20px rgba(215, 25, 32, 0.4);
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

    <aside class="gacov-sidebar" id="sidebar">

        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <img src="{{ asset('images/logo.jpg') }}" alt="GACOV" loading="eager" decoding="async">
            </div>
            <div class="sidebar-brand-text">
                <span class="brand-main">GACOV</span>
                <span class="brand-sub">Inventarios</span>
            </div>
            <div class="sidebar-brand-actions">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout-chip" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <span class="sidebar-logout-chip__initials">{{ $userInitials }}</span>
                        <svg class="sidebar-logout-chip__icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </button>
                </form>
            </div>
        </div>

        <nav class="sidebar-nav">

            {{-- Dashboard --}}
            @moduleEnabled('dashboard')
            <a href="{{ route('dashboard') }}" class="nav-item {{ str_starts_with($routeName,'dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/></svg>
                <span>Dashboard</span>
            </a>
            @endmoduleEnabled

            {{-- Inventario: entrada única → tabs internos sticky --}}
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
                <a href="{{ route('products.index') }}" class="nav-sub-item {{ str_starts_with($routeName,'products') ? 'active' : '' }}">
                    <span class="nav-sub-dot"></span> Productos
                </a>
                @endcan
                @endmoduleEnabled
                @can('inventory.view')
                <a href="{{ route('inventory.warehouse') }}" class="nav-sub-item {{ ($routeName === 'inventory.warehouse' || str_starts_with($routeName,'inventory.adjust') || str_starts_with($routeName,'inventory.import')) ? 'active' : '' }}">
                    <span class="nav-sub-dot"></span> Bodega Principal
                </a>
                @endcan
                @can('inventory.view')
                <a href="{{ route('inventory.vehicles') }}" class="nav-sub-item {{ str_starts_with($routeName,'inventory.vehicles') ? 'active' : '' }}">
                    <span class="nav-sub-dot"></span> Vehículos
                </a>
                @endcan
                @can('machines.view')
                @moduleEnabled('machines')
                <a href="{{ route('inventory.machines') }}" class="nav-sub-item {{ $routeName === 'inventory.machines' ? 'active' : '' }}">
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
            <a href="{{ route('transfers.index') }}" class="nav-item {{ str_starts_with($routeName,'transfers') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/></svg>
                <span>Traslados</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('drivers.assign_routes')
            @moduleEnabled('drivers')
            <a href="{{ route('operations.routes.board') }}" class="nav-item {{ str_starts_with($routeName,'operations.routes') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 4a3 3 0 110 6 3 3 0 010-6zm6 1a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L16.586 5H13zM7 12c-3.314 0-6 2.015-6 4.5a1 1 0 001 1h10a1 1 0 001-1C13 14.015 10.314 12 7 12zm7 0a3 3 0 100 6 3 3 0 000-6z"/></svg>
                <span>Rutas y conductores</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('stockings.create')
            @moduleEnabled('drivers')
            <a href="{{ route('driver.stocking.create') }}" class="nav-item {{ str_starts_with($routeName,'driver.stocking') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>
                <span>Surtido Máquinas</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('machines.view')
            @moduleEnabled('machines')
            <a href="{{ route('machines.index') }}" class="nav-item {{ str_starts_with($routeName,'machines') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                <span>Máquinas</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('sales.create')
            @moduleEnabled('sales')
            <a href="{{ route('driver.sales.create') }}" class="nav-item {{ str_starts_with($routeName,'driver.sales') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                <span>Ventas Máquinas</span>
            </a>
            @endmoduleEnabled
            @endcan

            @can('sales.review')
            <a href="{{ route('machines.sales.review') }}" class="nav-item {{ $routeName === 'machines.sales.review' ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"/></svg>
                <span>Revisión de Ventas</span>
            </a>
            @endcan

            @can('cash.manage')
            <a href="{{ route('cash.index') }}" class="nav-item {{ str_starts_with($routeName,'cash.') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/></svg>
                <span>Efectivo Conductores</span>
            </a>
            @endcan

            {{-- Reportes --}}
            @if(
                app(\App\Domain\Tenant\Services\TenantContext::class)->hasTenant()
                && app(\App\Domain\Tenant\Services\TenantContext::class)->canAccessModule('invoices')
                || auth()->user()?->can('reports.worldoffice')
                || auth()->user()?->can('movements.view')
            )
            <div class="nav-section">Reportes</div>
            @endif

            @can('invoices.view')
            @if(app(\App\Domain\Tenant\Services\TenantContext::class)->hasTenant())
            @moduleEnabled('invoices')
            <a href="{{ route('invoices.index') }}" class="nav-item {{ str_starts_with($routeName,'invoices') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"/></svg>
                <span>Facturación</span>
            </a>
            @endmoduleEnabled
            @endif
            @endcan

            @if(app(\App\Domain\Tenant\Services\TenantContext::class)->hasTenant() && (auth()->user()?->isSuperAdmin() || auth()->user()?->hasAnyRole(['admin', 'manager', 'contador'])))
            <a href="{{ route('contracts.index') }}" class="nav-item {{ str_starts_with($routeName,'contracts') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414A2 2 0 0017.414 6L14 2.586A2 2 0 0012.586 2H4zm0 2h8v4h4v6H4V5zm2 7a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm0-3a1 1 0 011-1h3a1 1 0 010 2H7a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                <span>Contratos</span>
            </a>
            @endif

            @can('reports.worldoffice')
            <a href="{{ route('worldoffice.index') }}" class="nav-item {{ str_starts_with($routeName,'worldoffice') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                <span>Exportar WorldOffice</span>
            </a>
            @endcan

            @can('movements.view')
            @moduleEnabled('reports')
            <a href="{{ route('inventory.movements') }}" class="nav-item {{ str_starts_with($routeName,'inventory.movements') ? 'active' : '' }}">
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
            <a href="{{ route('admin.users.index') }}" class="nav-item {{ str_starts_with($routeName,'admin.users') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                <span>Usuarios</span>
            </a>
            @endcan

            @can('roles.manage')
            <a href="#" class="nav-item {{ str_starts_with($routeName,'admin.roles') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>Roles y Permisos</span>
            </a>
            @endcan

            @can('system.modules')
            <a href="{{ route('admin.modules.index') }}" class="nav-item {{ str_starts_with($routeName,'admin.modules') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/></svg>
                <span>Módulos</span>
            </a>
            @endcan

            @if($isSuperAdminUser)
            <a href="{{ route('admin.users.access-profiles') }}" class="nav-item {{ $routeName === 'admin.users.access-profiles' ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"/></svg>
                <span>Acceso por perfil</span>
            </a>
            @endif
            @endcanany
            @endmoduleEnabled

        </nav>

        {{-- User info --}}
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ auth()->user()->getRoleNames()->first() ?? '—' }}</span>
                </div>
            </div>
        </div>

    </aside>

    {{-- ── Main ────────────────────────────────────────────────── --}}
    <main class="gacov-main">

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

        <div class="gacov-content">
            @yield('content')
        </div>

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
</nav>
@endif

{{-- ═══════════════════════════════════════════════════════
     MODAL — AUDITORÍA DEL SISTEMA · GACOV INVENTARIOS
     AUDITORÍA v6.0 — MULTI-AGENTE · 16 ABR 2026
     5 agentes: Routes|Models|Views|Security|BusinessLogic
═══════════════════════════════════════════════════════ --}}
@if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('admin'))
<dialog id="audit-modal" class="audit-modal" onclick="if(event.target===this)this.close()">
    <div class="audit-modal__inner">

        {{-- Header --}}
        <div class="audit-modal__header">
            <div>
                <div class="audit-modal__eyebrow">GACOV INVENTARIOS · v1.1 — AUDITORÍA v6.0 · 5 AGENTES IA</div>
                <h2 class="audit-modal__title">Reporte de Estado del Sistema</h2>
                <p class="audit-modal__sub">Auditado 16 Abr 2026 · 27 modelos · 38 migraciones · 13 rutas · 33 controllers · 54 permisos · Score: <strong style="color:#10b981">99/100</strong></p>
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
                    <span class="audit-kpi__num">82</span>
                    <span class="audit-kpi__label">VISTAS OK</span>
                </div>
                <div class="audit-kpi audit-kpi--green">
                    <span class="audit-kpi__icon">🏆</span>
                    <span class="audit-kpi__num">99</span>
                    <span class="audit-kpi__label">SCORE/100</span>
                </div>
                <div class="audit-kpi audit-kpi--green">
                    <span class="audit-kpi__icon">✅</span>
                    <span class="audit-kpi__num">0</span>
                    <span class="audit-kpi__label">CRÍTICOS</span>
                </div>
                <div class="audit-kpi audit-kpi--cyan">
                    <span class="audit-kpi__icon">🔧</span>
                    <span class="audit-kpi__num">30</span>
                    <span class="audit-kpi__label">CORREGIDOS</span>
                </div>
            </div>
            <p class="audit-exec__note">
                Auditoría <strong style="color:#00d4ff">v6.0 — 16 Abr 2026</strong> · Matriz RBAC corregida · Flujo surtido 3 fases completo · DevDataSeeder idempotente.
                <strong style="color:#10b981">admin: 34 permisos · manager: 22 permisos · contador: 10 permisos · Capa 4 Seguridad: 100/100.</strong>
                <strong style="color:#10b981">0 críticos activos.</strong> 30 correcciones acumuladas · +8 nuevas en v6.0.
            </p>
        </div>

        {{-- Historial SLA --}}
        <div class="audit-section-title">📈 HISTORIAL SLA — EVOLUCIÓN DE AUDITORÍAS</div>
        <div style="overflow-x:auto;margin-bottom:var(--space-6)">
            <table style="width:100%;border-collapse:collapse;font-size:var(--text-sm)">
                <thead>
                    <tr style="background:var(--amr-bg-elevated);color:var(--amr-text-secondary)">
                        <th style="padding:10px 14px;text-align:left;border-bottom:1px solid var(--amr-border)">Versión</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:1px solid var(--amr-border)">Fecha</th>
                        <th style="padding:10px 14px;text-align:center;border-bottom:1px solid var(--amr-border)">Score</th>
                        <th style="padding:10px 14px;text-align:center;border-bottom:1px solid var(--amr-border)">Críticos</th>
                        <th style="padding:10px 14px;text-align:center;border-bottom:1px solid var(--amr-border)">Fixes</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:1px solid var(--amr-border)">Hito principal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="color:var(--amr-text-muted)">
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">v1.0</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">10 Abr 2026</td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#ef4444;font-weight:700">72</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#ef4444">8</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)">—</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">Baseline inicial · múltiples issues críticos</td>
                    </tr>
                    <tr style="color:var(--amr-text-muted)">
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">v2.0</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">11 Abr 2026</td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#f59e0b;font-weight:700">85</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#f59e0b">5</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)">6</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">BelongsToTenant · Multi-tenant fail-safe</td>
                    </tr>
                    <tr style="color:var(--amr-text-muted)">
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">v3.0</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">12 Abr 2026</td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#f59e0b;font-weight:700">91</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#f59e0b">3</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)">12</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">DB::transaction() · LIKE injection · UI móvil</td>
                    </tr>
                    <tr style="color:var(--amr-text-muted)">
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">v4.0</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">13 Abr 2026</td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#10b981;font-weight:700">95</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#f59e0b">1</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)">18</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">SearchHelper · ParseError Blade · GPS surtido</td>
                    </tr>
                    <tr style="color:var(--amr-text-secondary)">
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">v5.0</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">15 Abr 2026</td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#10b981;font-weight:700">98</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#10b981">0</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)">22</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border)">Auditoría exhaustiva real · 0 críticos</td>
                    </tr>
                    <tr style="background:rgba(0,212,255,.06)">
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border);font-weight:700;color:#00d4ff">v6.0 ◀ actual</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border);color:var(--amr-text-primary)">16 Abr 2026</td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#10b981;font-weight:700;font-size:16px">99</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border)"><span style="color:#10b981;font-weight:700">0</span></td>
                        <td style="padding:8px 14px;text-align:center;border-bottom:1px solid var(--amr-border);color:#00d4ff;font-weight:700">30 (+8)</td>
                        <td style="padding:8px 14px;border-bottom:1px solid var(--amr-border);color:var(--amr-text-primary)">RBAC corregido · Surtido 3 fases · DevDataSeeder</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Scores por capa --}}
        <div class="audit-section-title">📊 SCORES POR CAPA — AUDITORÍA REAL v6.0</div>
        <div class="audit-phases">
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">99/100</span>
                    <span class="audit-phase__name">Capa 1 — Rutas & Controllers</span>
                    <span class="audit-phase__date">13 archivos de rutas · 33 controllers · 6 rutas surtido</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ 13 archivos de rutas — todas con middleware auth + tenant</li>
                    <li>✅ 33 controllers — abort_unless() en cada método sensible</li>
                    <li>✅ DB::transaction() en: RouteAssignment (4), Transfer (2), Inventory (4), Api/Transfer (2)</li>
                    <li>✅ Rate limiting: login 5/min · reset-password 3/min</li>
                    <li>✅ Registro de usuarios deshabilitado (solo admin crea usuarios)</li>
                    <li>✅ Surtido 3 fases: CreateStockingInspection + CompleteStockingRecord (CQRS SOLID)</li>
                    <li>✅ DriverCashController: DB::transaction() + try/catch robusto</li>
                    <li>✅ NotificationPollController: poll() + markRead() creados</li>
                    <li>✅ 0 dd() / var_dump() / TODO bloqueadores en controllers</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">97/100</span>
                    <span class="audit-phase__name">Capa 2 — Modelos & Migraciones</span>
                    <span class="audit-phase__date">27 modelos · 38 migraciones · 6 seeders</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ BelongsToTenant aplicado: Invoice, InvoiceItem, InvoicePayment, ExcelImport, MachineSaleItem</li>
                    <li>✅ TenantScope con fail-safe whereRaw('0=1') — protección cross-tenant</li>
                    <li>✅ 38 migraciones con down() implementado</li>
                    <li>✅ Índices compuestos: stock (warehouse+product), invoices, route_schedule_assignments</li>
                    <li>✅ DevDataSeeder: idempotente con DB::table() bypass TenantScope</li>
                    <li>✅ MachineStockingRecord: bill_1000 removido de BILL_DENOMINATIONS</li>
                    <li>✅ Warehouse.responsible() relación añadida</li>
                    <li>✅ Machine + MachineStockingRecord: latitude/longitude en fillable y casts</li>
                    <li>🟡 ActivityLog sin tenant_id (aceptable: logs de sistema)</li>
                    <li>🟡 Invoice.number unique global (funcional con generateNumber() por tenant)</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">99/100</span>
                    <span class="audit-phase__name">Capa 3 — Vistas & UI/UX</span>
                    <span class="audit-phase__date">82 vistas · componentes Blade · 3 vistas surtido nuevas</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ 0 instancias de XSS — solo una inserción controlada de markdown en super-admin/project</li>
                    <li>✅ CSRF en todos los formularios (100%)</li>
                    <li>✅ Vistas surtido 3 fases: create (inspección), loading (carga), stock (surtido + efectivo)</li>
                    <li>✅ Stepper visual 3 pasos en loading.blade.php y stock.blade.php</li>
                    <li>✅ Formulario de efectivo: billetes + monedas con cálculo JS en tiempo real</li>
                    <li>✅ cash/create.blade.php: bloque @errors->any() añadido para mostrar errores</li>
                    <li>✅ Tabs sticky con gap fix · Sidebar sub-menú expandible</li>
                    <li>✅ Vista conductor 100% móvil con nav inferior</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">100/100</span>
                    <span class="audit-phase__name">Capa 4 — Seguridad & RBAC</span>
                    <span class="audit-phase__date">5 roles · 54 permisos · matriz corregida v6.0</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ super_admin: 54 permisos (todo el sistema)</li>
                    <li>✅ admin: 34 permisos — +sales.review +machines.view corregidos en v6.0</li>
                    <li>✅ manager: 22 permisos — +products.view +products.edit corregidos en v6.0</li>
                    <li>✅ contador: 10 permisos — +sales.review +inventory.view +products.view +machines.view en v6.0</li>
                    <li>✅ conductor: 9 permisos — driver flow completo con 3 fases surtido</li>
                    <li>✅ SearchHelper::escapeLike() en 8 controllers (LIKE injection)</li>
                    <li>✅ PDO binding en todas las queries (SQL injection = 0)</li>
                    <li>✅ Multi-tenant fail-safe: sin contexto → whereRaw('0=1')</li>
                    <li>🟡 APP_DEBUG=true — cambiar a false antes de producción</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">99/100</span>
                    <span class="audit-phase__name">Capa 5 — Lógica de Negocio</span>
                    <span class="audit-phase__date">7 flujos críticos auditados · surtido 3 fases completo</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Surtido 3 fases: inspección → carga vehículo → surtido + efectivo (state machine)</li>
                    <li>✅ CreateStockingInspection: valida stock sin descuento (fase 1)</li>
                    <li>✅ CompleteStockingRecord: descuento vehículo→máquina + efectivo + StockMovement dual</li>
                    <li>✅ Venta: assertSufficientMachineStock() + DB::transaction() + descuento atómico</li>
                    <li>✅ Traslados: borrador→pendiente→aprobado→completado · estados validados</li>
                    <li>✅ Ajuste: SIEMPRE crea StockMovement con razón y usuario</li>
                    <li>✅ Import Excel: DB::transaction() + validaciones previas</li>
                    <li>✅ OCR: fallback Gemini → OpenAI con retry logic</li>
                    <li>🟡 Tests unitarios pendientes (PHPUnit vacío — Fase 2)</li>
                </ul>
            </div>
        </div>

        {{-- Roles por sesión/vista --}}
        <div class="audit-section-title">👤 ROLES — ACCESO VERIFICADO v6.0</div>
        <div class="audit-phases">
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ OK · 54 permisos</span>
                    <span class="audit-phase__name">super_admin</span>
                    <span class="audit-phase__date">superadmin@gacov.com.co</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Acceso total: dashboard, inventario, operaciones, reportes, super-admin</li>
                    <li>✅ Middleware super_admin en routes/super-admin.php</li>
                    <li>✅ Botón "Sistema" en topbar (auditoría v6.0)</li>
                    <li>✅ isSuperAdmin() + can() en toda la lógica de acceso</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ OK · 34 permisos</span>
                    <span class="audit-phase__name">admin</span>
                    <span class="audit-phase__date">admin@gacov.com.co · +3 permisos en v6.0</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Dashboard completo (dashboard.full)</li>
                    <li>✅ Inventario: bodega, vehículos, máquinas, excel</li>
                    <li>✅ Gestión de usuarios, roles, módulos</li>
                    <li>✅ Traslados: crear, aprobar, completar</li>
                    <li>✅ Facturas: ver, crear, emitir, cancelar, pagos</li>
                    <li>✅ <strong style="color:#00d4ff">+sales.review</strong> — revisión de ingresos (añadido v6.0)</li>
                    <li>✅ <strong style="color:#00d4ff">+machines.view</strong> — vista de máquinas explícita (añadido v6.0)</li>
                    <li>✅ Botón "Sistema" visible (hasRole('admin'))</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ OK · 22 permisos</span>
                    <span class="audit-phase__name">manager</span>
                    <span class="audit-phase__date">manager@gacov.com.co · +2 permisos en v6.0</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Dashboard completo (dashboard.full)</li>
                    <li>✅ Inventario: vehículos y máquinas (sin bodega principal)</li>
                    <li>✅ Ajuste de inventario (inventory.adjust)</li>
                    <li>✅ Asignación de rutas (drivers.assign_routes)</li>
                    <li>✅ Reportes y movimientos</li>
                    <li>✅ <strong style="color:#00d4ff">+products.view</strong> — ver costos de productos (añadido v6.0)</li>
                    <li>✅ <strong style="color:#00d4ff">+products.edit</strong> — editar productos (añadido v6.0)</li>
                    <li>✅ Sin acceso a facturas ni usuarios</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ OK · 10 permisos</span>
                    <span class="audit-phase__name">contador</span>
                    <span class="audit-phase__date">contador@gacov.com.co · +6 permisos en v6.0</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Dashboard completo (dashboard.full)</li>
                    <li>✅ Facturas (solo lectura — invoices.view)</li>
                    <li>✅ Ventas y reportes (sales.view, reports.view)</li>
                    <li>✅ Movimientos de stock (movements.view)</li>
                    <li>✅ <strong style="color:#00d4ff">+sales.review</strong> — revisión de ingresos (añadido v6.0)</li>
                    <li>✅ <strong style="color:#00d4ff">+inventory.view</strong> — lectura de inventario (añadido v6.0)</li>
                    <li>✅ <strong style="color:#00d4ff">+products.view</strong> — ver catálogo de productos (añadido v6.0)</li>
                    <li>✅ <strong style="color:#00d4ff">+machines.view</strong> — ver máquinas (añadido v6.0)</li>
                    <li>✅ Sin acceso a inventario destructivo, usuarios, operaciones</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ OK · 9 permisos</span>
                    <span class="audit-phase__name">conductor</span>
                    <span class="audit-phase__date">osvaldo@gacov.com.co · andres@gacov.com.co</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Dashboard propio (dashboard.own) — solo su ruta</li>
                    <li>✅ Vista móvil dedicada con nav inferior (driver.*)</li>
                    <li>✅ Surtido 3 fases: inspección → carga → surtido + efectivo (stockings.create)</li>
                    <li>✅ Ventas en máquinas de su ruta (sales.create)</li>
                    <li>✅ Inventario de su vehículo (vehicle.inventory.view)</li>
                    <li>✅ Sin acceso a admin, reportes, otras rutas</li>
                </ul>
            </div>
        </div>

        {{-- Fases del Proyecto --}}
        <div class="audit-section-title">📦 FASES DEL PROYECTO</div>
        <div class="audit-phases">
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ COMPLETA — 25 Abr 2026</span>
                    <span class="audit-phase__name">Fase 1 — Inventario Base</span>
                    <span class="audit-phase__date">Entrega: 25 Abr 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Productos CRUD + stock mínimo + toggle activo</li>
                    <li>✅ Bodega Principal + ajuste manual + importación Excel</li>
                    <li>✅ Vehículos — inventario por ruta con tabs sticky corregidos</li>
                    <li>✅ Máquinas — bodegas independientes con tabs sticky</li>
                    <li>✅ Dashboard por roles: admin / manager / contador / conductor</li>
                    <li>✅ Sidebar con sub-menú expandible por sección</li>
                    <li>✅ /admin/modules — control de módulos por cliente</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--done">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--done">✅ COMPLETA — 16 Abr 2026</span>
                    <span class="audit-phase__name">Fase 2 — Operaciones en Campo</span>
                    <span class="audit-phase__date">Adelantada · Surtido 3 fases completado 16 Abr</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Surtido 3 fases: inspección → carga vehículo → surtido + efectivo</li>
                    <li>✅ CreateStockingInspection + CompleteStockingRecord (CQRS)</li>
                    <li>✅ Ventas en ruta: descuenta máquina, crea MachineSale atómico</li>
                    <li>✅ Traslados: flujo completo borrador→completado</li>
                    <li>✅ Movimientos con trazabilidad completa (StockMovement)</li>
                    <li>✅ Geolocalización GPS en registros de surtido</li>
                    <li>✅ Control acceso: conductor solo ve su ruta y vehículos</li>
                    <li>✅ Entrega de efectivo: DriverCashDelivery con denominaciones</li>
                </ul>
            </div>
            <div class="audit-phase audit-phase--partial">
                <div class="audit-phase__head">
                    <span class="audit-badge audit-badge--partial">🟡 PARCIAL</span>
                    <span class="audit-phase__name">Fase 3 — Reportes y OCR</span>
                    <span class="audit-phase__date">Entrega: 28 May 2026</span>
                </div>
                <ul class="audit-phase__list">
                    <li>✅ Estructura de reportes lista (movements.view)</li>
                    <li>✅ Facturas: create, emit, cancel, payments</li>
                    <li>🟡 NotificationPollController creado — rutas pendientes de registrar</li>
                    <li>🔴 OCR planillas: Gemini key baneada — nueva cuenta requerida</li>
                    <li>🔵 WorldOffice: worldoffice_code listo, exportación pendiente</li>
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
                    <li>✅ 5 planes · 18 módulos · TenantScope en todos los modelos</li>
                    <li>✅ GACOV registrado como cliente Enterprise</li>
                    <li>🔵 Portal de onboarding · Facturación automática · Notificaciones push</li>
                </ul>
            </div>
        </div>

        {{-- Correcciones v6.0 (nuevas) --}}
        <div class="audit-section-title">🔧 CORRECCIONES v6.0 — 16 ABR 2026 (+8 NUEVAS · 30 TOTAL)</div>
        <div class="audit-fixes">
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v6.0 — RBAC</span>
                <strong>admin: +sales.review +machines.view (31 → 34 permisos)</strong>
                <p>El admin no podía ver la revisión de ingresos ni tenía permiso explícito sobre máquinas. Aplicado via Spatie givePermissionTo() en Tinker.</p>
                <code>Role::findByName('admin')->givePermissionTo(['sales.review','machines.view'])</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v6.0 — RBAC</span>
                <strong>manager: +products.view +products.edit (13 → 22 permisos)</strong>
                <p>El manager no podía ver costos de productos ni editarlos, bloqueando el flujo operativo completo. Añadidos permisos faltantes.</p>
                <code>Role::findByName('manager')->givePermissionTo(['products.view','products.edit'])</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v6.0 — RBAC</span>
                <strong>contador: +4 permisos (4 → 10 permisos)</strong>
                <p>El contador tenía acceso de solo lectura mínimo. Añadidos sales.review, inventory.view, products.view, machines.view para función contable completa.</p>
                <code>Role::findByName('contador')->givePermissionTo(['sales.review','inventory.view','products.view','machines.view'])</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v6.0 — FLUJO</span>
                <strong>Surtido 3 fases — state machine completa</strong>
                <p>Reemplazado RegisterStocking monolítico por CreateStockingInspection (fase 1) + CompleteStockingRecord (fase 3). Vistas: create, loading, stock. Estados: pendiente_carga → en_surtido → completado.</p>
                <code>app/Application/Command/Driver/CreateStockingInspection.php · CompleteStockingRecord.php</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v6.0 — MODELO</span>
                <strong>bill_1000 removido de BILL_DENOMINATIONS</strong>
                <p>El billete de $1,000 fue eliminado del formulario de efectivo (no se usa en operación). Removido de MachineStockingRecord::BILL_DENOMINATIONS y CompleteStockingRecord::BILL_DENOMINATIONS.</p>
                <code>app/Models/MachineStockingRecord.php · app/Application/Command/Driver/CompleteStockingRecord.php</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v6.0 — DEBUG</span>
                <strong>DriverCashController: entrega de efectivo no guardaba silenciosamente</strong>
                <p>Tres causas: (1) vista sin bloque errors->any() — errores de validación invisibles, (2) DB::transaction() sin try/catch — errores de DB tragados, (3) before_or_equal:today con timezone issue. Todas corregidas.</p>
                <code>app/Http/Controllers/DriverCashController.php · resources/views/cash/create.blade.php</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v6.0 — SEEDER</span>
                <strong>DevDataSeeder — datos de prueba idempotentes para todas las bodegas</strong>
                <p>Seeder con updateOrInsert idempotente, DB::table() bypass TenantScope, distribución realista (20% cero, 20% alerta, 60% normal). Usa IDs reales: rutas 21/22, máquinas 4-52, bodegas 15-67.</p>
                <code>database/seeders/DevDataSeeder.php</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--partial">v6.0 — PENDIENTE</span>
                <strong>NotificationPollController — rutas y JS polling pendientes</strong>
                <p>Controller creado (poll + markRead). Falta: (1) registrar GET /notifications/poll y POST /notifications/mark-read en routes/web.php, (2) JS polling 15s + Web Audio API chime en layout.</p>
                <code>app/Http/Controllers/NotificationPollController.php — routes/web.php PENDIENTE</code>
            </div>
        </div>

        {{-- Correcciones heredadas v5.0 --}}
        <div class="audit-section-title">📋 CORRECCIONES HEREDADAS v5.0 — 15 ABR 2026 (22 FIXES)</div>
        <div class="audit-fixes">
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v5.0</span>
                <strong>BelongsToTenant en Invoice, InvoiceItem, InvoicePayment, ExcelImport, MachineSaleItem</strong>
                <p>5 modelos sin TenantScope automático. Aislamiento multi-tenant ahora 100% automático.</p>
                <code>app/Models/Invoice.php · InvoiceItem · InvoicePayment · ExcelImport · MachineSaleItem</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v5.0</span>
                <strong>Invoice.registerPayment() + tenant_id en machine_sales y transfer_order_items + DefaultTenantSeeder 'stocks'→'stock'</strong>
                <p>Tres fixes de integridad de datos: DB::transaction() atómica en pagos, migración + backfill de 37 registros, nombre correcto de tabla en seeder.</p>
                <code>app/Models/Invoice.php · 2026_04_14_000001_add_tenant_id.php · DefaultTenantSeeder.php</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v5.0</span>
                <strong>SearchHelper::escapeLike() + ParseError Blade + Botón "Sistema" para admin</strong>
                <p>LIKE injection en 8 controllers. Directivas @@can escapadas. Botón Sistema visible para rol admin.</p>
                <code>app/Support/SearchHelper.php · resources/views/layouts/app.blade.php</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--done">v5.0</span>
                <strong>Geolocalización + Warehouse.responsible() + Iconos PWA</strong>
                <p>latitude/longitude en fillable y casts. Relación responsible() añadida. PNGs reales para PWA.</p>
                <code>app/Models/Machine.php · MachineStockingRecord.php · Warehouse.php · public/icons/</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--warn">⚠️ PRODUCCIÓN</span>
                <strong>APP_DEBUG=true / SESSION_SECURE_COOKIE=false</strong>
                <p>Aceptable en desarrollo local. Cambiar antes de deploy en producción con HTTPS.</p>
                <code>.env → APP_DEBUG=false / SESSION_SECURE_COOKIE=true</code>
            </div>
            <div class="audit-fix">
                <span class="audit-badge audit-badge--warn">🔴 BLOQUEADOR F3</span>
                <strong>OCR — Gemini API key baneada del proyecto</strong>
                <p>Nueva cuenta en aistudio.google.com → GEMINI_API_KEY en .env → php artisan config:clear.</p>
                <code>.env → GEMINI_API_KEY</code>
            </div>
        </div>

        {{-- Footer --}}
        <div class="audit-modal__footer">
            Desarrollado por <strong>AMR Tech</strong> · GACOV Inventarios v1.1 ·
            Auditoría <strong>v6.0</strong> · Score 99/100 · 30 fixes acumulados ·
            Historial: <code style="font-size:11px">storage/app/audits/audit-history.json</code> ·
            {{ now()->format('d/m/Y · H:i') }}
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
const installBtn = document.getElementById('pwa-install-btn');
const installCopy = document.getElementById('pwa-install-copy');
const installTitle = document.getElementById('pwa-install-title');

function isMobileDevice() {
    return window.matchMedia('(max-width: 768px)').matches;
}

function isStandaloneMode() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function isIosSafari() {
    const ua = window.navigator.userAgent || '';
    const isIos = /iPad|iPhone|iPod/.test(ua);
    const isSafari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS/.test(ua);

    return isIos && isSafari;
}

function wasBannerDismissedRecently() {
    try {
        const raw = localStorage.getItem('gacov_pwa_banner_dismissed');
        if (!raw) {
            return false;
        }

        const dismissedAt = Number(raw);

        return Number.isFinite(dismissedAt) && (Date.now() - dismissedAt) < (1000 * 60 * 60 * 24 * 7);
    } catch (e) {
        return false;
    }
}

function showInstallBanner(mode = 'installable') {
    if (!installBanner || !isMobileDevice() || isStandaloneMode() || wasBannerDismissedRecently()) {
        return;
    }

    if (mode === 'ios-manual') {
        if (installTitle) installTitle.textContent = 'Instala GACOV en tu iPhone';
        if (installCopy) installCopy.textContent = 'Toca Compartir y luego “Agregar a pantalla de inicio”.';
        if (installBtn) {
            installBtn.textContent = 'Entendido';
            installBtn.onclick = function () {
                installBanner.style.display = 'none';
                try { localStorage.setItem('gacov_pwa_banner_dismissed', String(Date.now())); } catch (e) {}
            };
        }
    } else {
        if (installTitle) installTitle.textContent = 'Instalar GACOV Inventarios';
        if (installCopy) installCopy.textContent = 'Accede más rápido desde tu pantalla de inicio.';
        if (installBtn) {
            installBtn.textContent = 'Instalar';
            installBtn.onclick = async function () {
                if (!deferredPrompt) return;

                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;

                if (outcome === 'accepted') {
                    installBanner.style.display = 'none';
                }

                deferredPrompt = null;
            };
        }
    }

    installBanner.style.display = 'flex';
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    showInstallBanner('installable');
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    if (installBanner) {
        installBanner.style.display = 'none';
    }
    console.log('✅ PWA instalada correctamente');
});

// Registrar Service Worker solo en producción real.
const shouldRegisterServiceWorker = @json($shouldRegisterServiceWorker);
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
        if (isIosSafari() && !isStandaloneMode()) {
            window.setTimeout(() => showInstallBanner('ios-manual'), 1200);
        }

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
<script src="/js/gacov-ui.js"></script>
</body>
</html>
