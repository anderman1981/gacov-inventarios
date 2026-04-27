@extends('layouts.app')
@section('title', 'Usuarios del sistema')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4)">
    <div>
        <h1 class="page-title">Usuarios del sistema</h1>
        <p class="page-subtitle">{{ $users->total() }} usuario(s) registrado(s)</p>
    </div>
    @can('users.create')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary" style="width:auto">
        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Nuevo usuario
    </a>
    @endcan
</div>

{{-- Filtros --}}
<div class="panel" style="margin-bottom:var(--space-6)">
    <div class="panel-body" style="padding:var(--space-4) var(--space-6)">
        <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-input" placeholder="Nombre o correo..." value="{{ request('search') }}">
            </div>
            <div class="form-group" style="min-width:160px;margin-bottom:0">
                <label class="form-label">Rol</label>
                <select name="role" class="form-input">
                    <option value="">Todos los roles</option>
                    @foreach($roles as $key => $label)
                    <option value="{{ $key }}" {{ request('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto">Filtrar</button>
            <a href="{{ route('admin.users.index') }}" style="padding:11px 16px;color:var(--gacov-text-muted);text-decoration:none;font-size:13px">Limpiar</a>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="panel">
    @if($users->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Ruta</th>
                <th>Estado</th>
                <th>Último acceso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            @php
                $roleName  = $user->getRoleNames()->first() ?? '';
                $roleBadge = match($roleName) {
                    'super_admin' => ['badge-error',   'Super Admin'],
                    'admin'       => ['badge-info',    'Admin'],
                    'manager'     => ['badge-neutral', 'Manager'],
                    'contador'    => ['badge-warning', 'Contador'],
                    'conductor'   => ['badge-success', 'Conductor'],
                    default       => ['badge-neutral', $roleName ?: '—'],
                };
                $isSelf = $user->id === auth()->id();
            @endphp
            <tr>
                {{-- Avatar + nombre --}}
                <td>
                    <div style="display:flex;align-items:center;gap:var(--space-3)">
                        <div style="width:34px;height:34px;border-radius:50%;background:var(--gacov-gradient);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <strong style="font-size:14px">{{ $user->name }}</strong>
                            @if($isSelf)
                            <span style="font-size:11px;color:var(--gacov-text-muted);margin-left:4px">(tú)</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="color:var(--gacov-text-secondary);font-size:13px">{{ $user->email }}</td>
                <td style="color:var(--gacov-text-secondary);font-size:13px">{{ $user->phone ?? '—' }}</td>
                <td>
                    <span class="badge {{ $roleBadge[0] }}">{{ $roleBadge[1] }}</span>
                </td>
                <td style="font-size:13px;color:var(--gacov-text-secondary)">
                    {{ $user->route?->name ?? '—' }}
                </td>
                <td>
                    @if($user->must_change_password)
                        <span class="badge badge-warning">Debe cambiar clave</span>
                    @elseif($user->is_active)
                        <span class="badge badge-success">Activo</span>
                    @else
                        <span class="badge badge-neutral">Inactivo</span>
                    @endif
                </td>
                <td style="font-size:12px;color:var(--gacov-text-muted)">
                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Nunca' }}
                </td>
                <td>
                    <div style="display:flex;gap:var(--space-2);align-items:center;flex-wrap:wrap">
                        @can('users.edit')
                        <a href="{{ route('admin.users.edit', $user) }}"
                           title="Editar usuario"
                           style="padding:4px 10px;background:rgba(0,212,255,.1);color:var(--gacov-primary);border-radius:var(--radius-sm);font-size:12px;text-decoration:none;white-space:nowrap">
                            Editar
                        </a>

                        @if(! $isSelf)
                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}" style="display:inline">
                            @csrf
                            <button type="submit"
                                title="{{ $user->is_active ? 'Desactivar usuario' : 'Activar usuario' }}"
                                style="padding:4px 10px;background:{{ $user->is_active ? 'rgba(245,158,11,.1)' : 'rgba(16,185,129,.1)' }};color:{{ $user->is_active ? 'var(--gacov-warning)' : 'var(--gacov-success)' }};border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer;white-space:nowrap">
                                {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" style="display:inline"
                              onsubmit="return confirm('¿Resetear la contraseña de «{{ $user->name }}»? La nueva clave temporal será: Gacov2026!')">
                            @csrf
                            <button type="submit"
                                title="Resetear contraseña"
                                style="padding:4px 10px;background:rgba(124,58,237,.1);color:var(--gacov-secondary);border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer;white-space:nowrap">
                                Resetear clave
                            </button>
                        </form>
                        @endcan

                        @can('users.delete')
                        @if(! $isSelf)
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display:inline"
                              onsubmit="return confirm('¿Desactivar al usuario «{{ $user->name }}»?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                style="padding:4px 10px;background:rgba(239,68,68,.1);color:var(--gacov-error);border:none;border-radius:var(--radius-sm);font-size:12px;cursor:pointer;white-space:nowrap">
                                Desactivar
                            </button>
                        </form>
                        @endif
                        @endcan
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Paginación --}}
    @if($users->hasPages())
    <div style="padding:var(--space-4) var(--space-6);border-top:1px solid var(--gacov-border)">
        {{ $users->links() }}
    </div>
    @endif

    @else
    <div class="panel-body" style="text-align:center;padding:var(--space-12) 0;color:var(--gacov-text-muted)">
        <svg viewBox="0 0 20 20" fill="currentColor" width="40" height="40" style="margin:0 auto var(--space-4);opacity:.3">
            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
        </svg>
        <p style="font-size:15px;font-weight:500;margin-bottom:var(--space-2)">Sin usuarios</p>
        <p style="font-size:13px">No se encontraron usuarios con los filtros actuales.</p>
    </div>
    @endif
</div>
@endsection
