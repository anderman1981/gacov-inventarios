@extends('layouts.app')

@section('title', 'Acceso por perfil')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Acceso por perfil de usuario</h1>
        <p class="page-subtitle">Mapa de permisos y módulos disponibles para cada rol del sistema</p>
    </div>
</div>

<style>
.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--space-6);
    margin-top: var(--space-6);
}

.profile-card {
    background: var(--gacov-bg-surface, #111827);
    border: 1px solid var(--gacov-border, #1F2937);
    border-radius: var(--radius-lg, 16px);
    padding: var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    transition: box-shadow 250ms ease, border-color 250ms ease;
}

.profile-card:hover {
    box-shadow: var(--shadow-lg, 0 8px 32px rgba(0,212,255,.12));
    border-color: var(--gacov-border-focus, #00D4FF);
}

.profile-card-header {
    display: flex;
    align-items: center;
    gap: var(--space-4);
}

.profile-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md, 10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.profile-title {
    font-size: var(--text-lg, 17px);
    font-weight: 600;
    color: var(--gacov-text-primary, #F9FAFB);
    line-height: 1.2;
}

.profile-description {
    font-size: var(--text-sm, 13px);
    color: var(--gacov-text-muted, #6B7280);
}

.profile-accesses {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.profile-access-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    font-size: var(--text-sm, 13px);
    color: var(--gacov-text-secondary, #9CA3AF);
    line-height: 1.4;
}

.profile-access-item .access-icon {
    flex-shrink: 0;
    margin-top: 1px;
    font-size: 12px;
}

.profile-access-item.locked {
    color: var(--gacov-text-muted, #6B7280);
}

.profile-footer {
    border-top: 1px solid var(--gacov-border, #1F2937);
    padding-top: var(--space-4);
}

.badge-amber  { background: rgba(245,158,11,.15); color: #F59E0B; border: 1px solid rgba(245,158,11,.3); }
.badge-cyan   { background: rgba(0,212,255,.1);   color: #00D4FF; border: 1px solid rgba(0,212,255,.25); }
.badge-violet { background: rgba(124,58,237,.15); color: #A78BFA; border: 1px solid rgba(124,58,237,.3); }
.badge-green  { background: rgba(16,185,129,.12); color: #10B981; border: 1px solid rgba(16,185,129,.3); }
.badge-blue   { background: rgba(59,130,246,.12); color: #60A5FA; border: 1px solid rgba(59,130,246,.3); }

.badge-role {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: var(--radius-full, 9999px);
    font-size: var(--text-xs, 11px);
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
}
</style>

<div class="profile-grid">

    {{-- ── Super Admin ─────────────────────────────────────────── --}}
    <div class="profile-card">
        <div class="profile-card-header">
            <div class="profile-icon" style="background:rgba(245,158,11,.15)">🛡️</div>
            <div>
                <div class="profile-title">Super Admin</div>
                <span class="badge-role badge-amber">super_admin</span>
            </div>
        </div>
        <p class="profile-description">Acceso total al sistema incluyendo el panel de control maestro AMR Tech. Sin restricciones.</p>
        <ul class="profile-accesses">
            <li class="profile-access-item"><span class="access-icon">✅</span> Todo el sistema sin excepciones</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Panel Maestro AMR Tech</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Gestión de tenants y planes</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Configuración global del sistema</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Módulos y permisos avanzados</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Logs del sistema y auditoría</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Todos los módulos de Admin</li>
        </ul>
        <div class="profile-footer">
            <a href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">Ver como Super Admin →</a>
        </div>
    </div>

    {{-- ── Admin ────────────────────────────────────────────────── --}}
    <div class="profile-card">
        <div class="profile-card-header">
            <div class="profile-icon" style="background:rgba(0,212,255,.1)">⚙️</div>
            <div>
                <div class="profile-title">Administrador</div>
                <span class="badge-role badge-cyan">admin</span>
            </div>
        </div>
        <p class="profile-description">Gestión completa del negocio. Acceso a todos los módulos operativos y configuración de usuarios.</p>
        <ul class="profile-accesses">
            <li class="profile-access-item"><span class="access-icon">✅</span> Dashboard completo con todos los KPIs</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Inventario (bodega, vehículos, máquinas)</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Gestión de productos</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Órdenes de traslado (crear, aprobar)</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Rutas y conductores</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Reportes y exportación WorldOffice</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Gestión de usuarios</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Ajustes de inventario</li>
        </ul>
        <div class="profile-footer">
            <a href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">Ver como Admin →</a>
        </div>
    </div>

    {{-- ── Manager ──────────────────────────────────────────────── --}}
    <div class="profile-card">
        <div class="profile-card-header">
            <div class="profile-icon" style="background:rgba(124,58,237,.15)">📋</div>
            <div>
                <div class="profile-title">Manager</div>
                <span class="badge-role badge-violet">manager</span>
            </div>
        </div>
        <p class="profile-description">Supervisión operativa. Puede gestionar traslados y surtidos pero no modifica configuraciones del sistema.</p>
        <ul class="profile-accesses">
            <li class="profile-access-item"><span class="access-icon">✅</span> Dashboard completo</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Inventario (solo lectura)</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Órdenes de traslado</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Surtido de máquinas</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Rutas (solo lectura)</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Máquinas</li>
            <li class="profile-access-item locked"><span class="access-icon">🔒</span> Gestión de usuarios (sin acceso)</li>
            <li class="profile-access-item locked"><span class="access-icon">🔒</span> Ajustes de inventario (sin acceso)</li>
        </ul>
        <div class="profile-footer">
            <a href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">Ver como Manager →</a>
        </div>
    </div>

    {{-- ── Contador ─────────────────────────────────────────────── --}}
    <div class="profile-card">
        <div class="profile-card-header">
            <div class="profile-icon" style="background:rgba(16,185,129,.12)">📊</div>
            <div>
                <div class="profile-title">Contador</div>
                <span class="badge-role badge-green">contador</span>
            </div>
        </div>
        <p class="profile-description">Enfocado en reportes financieros y exportación contable. Acceso de lectura a movimientos y exportación WorldOffice.</p>
        <ul class="profile-accesses">
            <li class="profile-access-item"><span class="access-icon">✅</span> Dashboard financiero</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Reportes de movimientos</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Exportación WorldOffice</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Ver inventario (solo lectura)</li>
            <li class="profile-access-item locked"><span class="access-icon">🔒</span> Crear/editar traslados (sin acceso)</li>
            <li class="profile-access-item locked"><span class="access-icon">🔒</span> Surtido de máquinas (sin acceso)</li>
            <li class="profile-access-item locked"><span class="access-icon">🔒</span> Gestión de usuarios (sin acceso)</li>
        </ul>
        <div class="profile-footer">
            <a href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">Ver como Contador →</a>
        </div>
    </div>

    {{-- ── Conductor ────────────────────────────────────────────── --}}
    <div class="profile-card">
        <div class="profile-card-header">
            <div class="profile-icon" style="background:rgba(59,130,246,.12)">🚚</div>
            <div>
                <div class="profile-title">Conductor (Ruta)</div>
                <span class="badge-role badge-blue">conductor</span>
            </div>
        </div>
        <p class="profile-description">Vista móvil optimizada para operación en campo. Acceso exclusivo a su ruta asignada y vehículo.</p>
        <ul class="profile-accesses">
            <li class="profile-access-item"><span class="access-icon">✅</span> Dashboard conductor móvil</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Mi inventario de vehículo</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Registrar surtido de máquinas</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Registrar ventas en máquinas</li>
            <li class="profile-access-item"><span class="access-icon">✅</span> Ver mi ruta asignada</li>
            <li class="profile-access-item locked"><span class="access-icon">🔒</span> Panel administrativo (sin acceso)</li>
            <li class="profile-access-item locked"><span class="access-icon">🔒</span> Inventario bodega (sin acceso)</li>
            <li class="profile-access-item locked"><span class="access-icon">🔒</span> Reportes y traslados (sin acceso)</li>
        </ul>
        <div class="profile-footer">
            <a href="{{ route('driver.dashboard') }}" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">Ver dashboard conductor →</a>
        </div>
    </div>

</div>

@endsection
