<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- PWA Meta Tags --}}
    <meta name="theme-color" content="#D71920">
    <meta name="description" content="Panel de administración SaaS para AMR Tech">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="AMR Admin">
    
    {{-- Icons --}}
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    
    {{-- Manifest --}}
    <link rel="manifest" href="/manifest.json">
    
    <title>@yield('title', 'Panel Maestro') — AMR Tech</title>
    @php($routeName = request()->route()?->getName() ?? '')
    @php($hasViteAssets = file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
    @if($hasViteAssets)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    @stack('styles')
    <style>
        .super-admin-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-1);
            background: rgba(215,25,32,.12);
            color: #D71920;
            border: 1px solid rgba(215,25,32,.24);
            border-radius: var(--radius-full);
            padding: 2px var(--space-3);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .super-admin-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #D71920;
            display: inline-block;
        }
        .amr-brand-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, #D71920 0%, #111111 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .amr-brand-icon svg {
            width: 18px;
            height: 18px;
        }
        .topbar-user-dropdown {
            position: relative;
        }
        .topbar-user-btn {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2) var(--space-3);
            background: var(--gacov-bg-elevated);
            border: 1px solid var(--gacov-border);
            border-radius: var(--radius-md);
            color: var(--gacov-text-primary);
            cursor: pointer;
            font-size: 13px;
            transition: border-color var(--transition);
        }
        .topbar-user-btn:hover {
            border-color: var(--gacov-primary);
        }
        .topbar-user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--gacov-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .dropdown-menu {
            position: absolute;
            top: calc(100% + var(--space-2));
            right: 0;
            background: var(--gacov-bg-surface);
            border: 1px solid var(--gacov-border);
            border-radius: var(--radius-md);
            min-width: 180px;
            box-shadow: var(--shadow-lg);
            z-index: var(--z-dropdown, 100);
            overflow: hidden;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-3) var(--space-4);
            color: var(--gacov-text-secondary);
            font-size: 13px;
            text-decoration: none;
            transition: background var(--transition), color var(--transition);
            cursor: pointer;
            width: 100%;
            background: none;
            border: none;
            text-align: left;
        }
        .dropdown-item:hover {
            background: var(--gacov-bg-elevated);
            color: var(--gacov-text-primary);
        }
        .dropdown-item svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }
        .dropdown-divider {
            height: 1px;
            background: var(--gacov-border);
            margin: var(--space-1) 0;
        }
    </style>
</head>
<body>

<div class="gacov-layout" id="app-layout">

    {{-- ── Sidebar ─────────────────────────────────────────────── --}}
    <aside class="gacov-sidebar" id="sidebar">

        {{-- Brand --}}
        <div class="sidebar-brand">
            <div class="amr-brand-icon">
                <svg viewBox="0 0 20 20" fill="none">
                    <path d="M10 2L2 7v11h6v-5h4v5h6V7L10 2z" fill="#fff" opacity=".9"/>
                </svg>
            </div>
            <div class="sidebar-brand-text">
                <span class="brand-main">AMR Tech</span>
                <span class="brand-sub">Panel Maestro</span>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="sidebar-nav">

            <a href="{{ route('super-admin.dashboard') }}"
               class="nav-item {{ $routeName === 'super-admin.dashboard' ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-section">Gestión SaaS</div>

            <a href="{{ route('super-admin.tenants.index') }}"
               class="nav-item {{ str_starts_with($routeName, 'super-admin.tenants') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                </svg>
                <span>Clientes</span>
            </a>

            <a href="{{ route('super-admin.plans.index') }}"
               class="nav-item {{ str_starts_with($routeName, 'super-admin.plans') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 10l1.293-1.293zm2.586 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L11.586 10l-1.293-1.293a1 1 0 011.414-1.414z" clip-rule="evenodd"/>
                </svg>
                <span>Planes de suscripción</span>
            </a>

            <a href="{{ route('super-admin.modules.index') }}"
               class="nav-item {{ str_starts_with($routeName, 'super-admin.modules') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                </svg>
                <span>Módulos</span>
            </a>

            <div class="nav-section">Sistema</div>

            <a href="{{ route('super-admin.project.index') }}"
               class="nav-item {{ str_starts_with($routeName, 'super-admin.project') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h4v-2H4V5h12v10h-4v2h4a2 2 0 002-2V5a2 2 0 00-2-2H4z"/>
                    <path d="M9 9a1 1 0 011-1h.01A1 1 0 0111 9v5a1 1 0 11-2 0V9zM10 6a1.25 1.25 0 100 2.5A1.25 1.25 0 0010 6z"/>
                </svg>
                <span>Proyecto y docs</span>
            </a>

            <a href="{{ route('dashboard') }}" class="nav-item">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                </svg>
                <span>Volver al sistema</span>
            </a>

        </nav>

        {{-- Footer --}}
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">Super Admin</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout" title="Cerrar sesión">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </form>
        </div>

    </aside>

    {{-- ── Main ────────────────────────────────────────────────── --}}
    <main class="gacov-main">

        {{-- Topbar --}}
        <header class="gacov-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menú">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                </svg>
            </button>

            <div class="topbar-breadcrumb">
                <span class="super-admin-badge">Super Administrador</span>
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" style="color:var(--gacov-text-muted);margin:0 var(--space-2)">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="topbar-page">@yield('title', 'Panel Maestro')</span>
            </div>

            <div class="topbar-actions">
                <div class="topbar-user-dropdown" x-data="{ open: false }" @click.outside="open = false">
                    <button class="topbar-user-btn" @click="open = !open">
                        <div class="topbar-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <span>{{ auth()->user()->name }}</span>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu" x-show="open" x-transition>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                            Mi perfil
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item" style="color:var(--gacov-error)">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="alert alert-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)">
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

        {{-- Contenido --}}
        <div class="gacov-content">
            @yield('content')
        </div>

        {{-- Footer --}}
        <footer class="gacov-footer">
            AMR Tech &copy; {{ date('Y') }}
            &nbsp;·&nbsp; Panel de administración SaaS — Uso exclusivo interno
        </footer>

    </main>
</div>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('app-layout').classList.toggle('sidebar-open');
});
</script>
@livewireScripts
@stack('scripts')
</body>
</html>
