@extends('super-admin.layout')

@section('title', 'Módulos del Sistema')

@section('content')
{{-- Header --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:var(--space-6);flex-wrap:wrap;">
    <div>
        <h1 class="page-title">Módulos del Sistema</h1>
        <p class="page-subtitle">Activa o desactiva módulos globalmente y configura overrides por cliente.</p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;">
        <a href="{{ route('super-admin.plans.index') }}" class="btn-secondary" style="text-decoration:none;">
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5z"/>
            </svg>
            Ver Planes
        </a>
    </div>
</div>

{{-- Stats Cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--space-4);margin-bottom:var(--space-6);">
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:32px;font-weight:700;color:var(--gacov-primary);font-family:var(--font-display);">
            {{ $stats['total_modules'] }}
        </div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">
            Módulos registrados
        </div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:32px;font-weight:700;color:var(--gacov-success);font-family:var(--font-display);">
            {{ $stats['active_modules'] }}
        </div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">
            Módulos activos
        </div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:32px;font-weight:700;color:var(--gacov-warning);font-family:var(--font-display);">
            {{ $stats['phase_count'] }}
        </div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">
            Fases configuradas
        </div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:32px;font-weight:700;color:var(--gacov-info);font-family:var(--font-display);">
            {{ $stats['tenant_overrides'] }}
        </div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">
            Overrides activos
        </div>
    </div>
</div>

{{-- Filtro por Fase --}}
<div class="panel" style="margin-bottom:var(--space-6);">
    <div class="panel-header">
        <h2 class="panel-title">Fases del Sistema</h2>
    </div>
    <div class="panel-body">
        <div style="display:flex;gap:var(--space-4);flex-wrap:wrap;">
            @foreach([1, 2, 3, 4, 5] as $phase)
                <div style="flex:1;min-width:140px;padding:var(--space-4);border-radius:var(--radius-md);background:var(--gacov-bg-elevated);border:1px solid var(--gacov-border);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-2);">
                        <span style="font-size:11px;font-weight:700;color:var(--gacov-primary);text-transform:uppercase;letter-spacing:.5px;">
                            Fase {{ $phase }}
                        </span>
                        <span class="badge-secondary">
                            {{ $modules->where('phase_required', $phase)->where('is_active', true)->count() }} módulos
                        </span>
                    </div>
                    <div style="font-size:12px;color:var(--gacov-text-secondary);">
                        @switch($phase)
                            @case(1) Inventario Base
                            @case(2) Operaciones
                            @case(3) Reportes y OCR
                            @case(4) Empresarial
                            @case(5) Enterprise
                        @endswitch
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Tabla de Módulos --}}
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Catálogo Modular</h2>
        <span style="font-size:13px;color:var(--gacov-text-muted);">
            Haz clic en el interruptor para activar/desactivar módulos globalmente.
        </span>
    </div>
    <div class="panel-body" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Clave</th>
                        <th>Fase</th>
                        <th>Ruta / Permisos</th>
                        <th>Overrides</th>
                        <th>Estado Global</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($modules as $module)
                        <tr>
                            {{-- Módulo --}}
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:{{ $module->color ?? '#0EA5E9' }}22;color:{{ $module->color ?? '#0EA5E9' }};font-size:18px;flex-shrink:0;">
                                        {{ $module->icon ?? '◈' }}
                                    </span>
                                    <div>
                                        <div style="font-weight:600;color:var(--gacov-text-primary);">{{ $module->name }}</div>
                                        @if($module->description)
                                            <div style="font-size:11px;color:var(--gacov-text-secondary);margin-top:2px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                {{ $module->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Clave --}}
                            <td>
                                <code style="font-size:11px;background:var(--gacov-bg-elevated);padding:2px 6px;border-radius:4px;color:var(--gacov-primary);">
                                    {{ $module->key }}
                                </code>
                            </td>

                            {{-- Fase --}}
                            <td>
                                <form action="{{ route('super-admin.modules.phase', $module) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <select name="phase_required" onchange="this.form.submit()" 
                                            style="background:var(--gacov-bg-elevated);border:1px solid var(--gacov-border);border-radius:var(--radius-sm);padding:4px 8px;font-size:11px;color:var(--gacov-text-primary);cursor:pointer;">
                                        @for($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" {{ $module->phase_required == $i ? 'selected' : '' }}>
                                                Fase {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                </form>
                            </td>

                            {{-- Ruta / Permisos --}}
                            <td style="font-size:11px;color:var(--gacov-text-secondary);">
                                <div>{{ $module->route_prefix ?: '—' }}</div>
                                <div style="color:var(--gacov-text-muted);">{{ $module->permission_prefix ?: '—' }}</div>
                            </td>

                            {{-- Overrides --}}
                            <td>
                                <div style="font-size:12px;">
                                    @if($module->enabled_overrides_count > 0)
                                        <span class="badge-success">{{ $module->enabled_overrides_count }} habilitados</span>
                                    @endif
                                    @if($module->disabled_overrides_count > 0)
                                        <span class="badge-error" style="margin-left:4px;">{{ $module->disabled_overrides_count }} bloqueados</span>
                                    @endif
                                    @if($module->enabled_overrides_count == 0 && $module->disabled_overrides_count == 0)
                                        <span style="color:var(--gacov-text-muted);">—</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Estado Global --}}
                            <td>
                                <form action="{{ route('super-admin.modules.toggle', $module) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="is_active" value="{{ $module->is_active ? '0' : '1' }}">
                                    <button type="submit" 
                                            style="width:48px;height:26px;border-radius:13px;background:{{ $module->is_active ? 'var(--gacov-success)' : 'var(--gacov-bg-elevated)' }};border:1px solid {{ $module->is_active ? 'var(--gacov-success)' : 'var(--gacov-border)' }};cursor:pointer;position:relative;transition:all .2s;"
                                            title="{{ $module->is_active ? 'Desactivar módulo' : 'Activar módulo' }}">
                                        <span style="display:block;width:20px;height:20px;border-radius:50%;background:#fff;position:absolute;top:2px;left:{{ $module->is_active ? '24px' : '2px' }};transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,.2);"></span>
                                    </button>
                                </form>
                            </td>

                            {{-- Acciones --}}
                            <td>
                                <div style="display:flex;gap:var(--space-2);">
                                    {{-- Dropdown de tenants --}}
                                    <div x-data="{ open: false }" style="position:relative;">
                                        <button @click="open = !open" 
                                                class="btn-icon" 
                                                style="padding:6px 10px;font-size:11px;background:var(--gacov-bg-elevated);border:1px solid var(--gacov-border);border-radius:var(--radius-sm);color:var(--gacov-text-secondary);cursor:pointer;display:flex;align-items:center;gap:4px;">
                                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
                                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                                            </svg>
                                            Clientes
                                        </button>
                                        <div x-show="open" 
                                             @click.away="open = false"
                                             x-transition
                                             style="position:absolute;right:0;top:calc(100% + 4px);background:var(--gacov-bg-surface);border:1px solid var(--gacov-border);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);min-width:200px;z-index:50;overflow:hidden;">
                                            <div style="padding:var(--space-3);border-bottom:1px solid var(--gacov-border);font-size:11px;font-weight:700;color:var(--gacov-text-muted);text-transform:uppercase;letter-spacing:.5px;">
                                                Asignar a cliente
                                            </div>
                                            @forelse($tenants as $tenant)
                                                <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--space-3);border-bottom:1px solid var(--gacov-border);font-size:12px;">
                                                    <span style="color:var(--gacov-text-primary);">{{ $tenant->name }}</span>
                                                    <div style="display:flex;gap:var(--space-1);">
                                                        <form action="{{ route('super-admin.modules.enable-tenant', [$module, $tenant]) }}" method="POST" style="display:inline;">
                                                            @csrf
                                                            <button type="submit" class="btn-icon" style="padding:4px 8px;background:var(--gacov-success);border:none;border-radius:4px;color:#fff;font-size:10px;cursor:pointer;" title="Habilitar">
                                                                ✓
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('super-admin.modules.disable-tenant', [$module, $tenant]) }}" method="POST" style="display:inline;">
                                                            @csrf
                                                            <button type="submit" class="btn-icon" style="padding:4px 8px;background:var(--gacov-error);border:none;border-radius:4px;color:#fff;font-size:10px;cursor:pointer;" title="Bloquear">
                                                                ✗
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @empty
                                                <div style="padding:var(--space-3);font-size:12px;color:var(--gacov-text-muted);text-align:center;">
                                                    No hay clientes registrados
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px;text-align:center;color:var(--gacov-text-muted);">
                                No hay módulos configurados todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Panel de Configuración por Cliente --}}
<div class="panel" style="margin-top:var(--space-6);">
    <div class="panel-header">
        <h2 class="panel-title">Configurar Módulos por Cliente</h2>
        <span style="font-size:13px;color:var(--gacov-text-muted);">
            Asigna rápidamente todos los módulos activos de una fase a un cliente.
        </span>
    </div>
    <div class="panel-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:var(--space-4);">
            @forelse($tenants as $tenant)
                <div style="padding:var(--space-4);border-radius:var(--radius-md);background:var(--gacov-bg-elevated);border:1px solid var(--gacov-border);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-3);">
                        <div>
                            <div style="font-weight:600;color:var(--gacov-text-primary);">{{ $tenant->name }}</div>
                            <div style="font-size:11px;color:var(--gacov-text-muted);">
                                {{ $tenant->subscription?->plan?->name ?? 'Sin plan' }}
                                · Fase activa F{{ $tenant->phase() }}
                            </div>
                        </div>
                        @if($tenant->is_active)
                            <span class="badge-success">Activo</span>
                        @else
                            <span class="badge-error">Suspendido</span>
                        @endif
                    </div>
                    
                    <form action="{{ route('super-admin.tenants.set-phase', $tenant) }}" method="POST" style="display:flex;gap:var(--space-2);">
                        @csrf
                        <select name="phase" style="flex:1;background:var(--gacov-bg-surface);border:1px solid var(--gacov-border);border-radius:var(--radius-sm);padding:var(--space-2) var(--space-3);font-size:13px;color:var(--gacov-text-primary);">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" @selected($tenant->phase() === $i)>
                                    Fase {{ $i }}
                                </option>
                            @endfor
                        </select>
                        <button type="submit" class="btn-primary" style="padding:var(--space-2) var(--space-4);">
                            Aplicar
                        </button>
                    </form>
                </div>
            @empty
                <div style="grid-column:1/-1;padding:48px;text-align:center;color:var(--gacov-text-muted);">
                    No hay clientes registrados.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
