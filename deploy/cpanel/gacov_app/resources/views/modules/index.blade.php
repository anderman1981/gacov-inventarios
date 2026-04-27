@extends('layouts.app')

@section('title', 'Módulos del Sistema')

@php
$companyName = $tenant?->name ?? 'Sistema GACOV';
@endphp

@section('content')
<div class="modules-page">

    {{-- Header Hero --}}
    <div class="modules-hero">
        <div class="hero-content">
            <div class="hero-badge">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                </svg>
                Tu Plan
            </div>
            <h1 class="hero-title">Módulos Disponibles</h1>
            <p class="hero-subtitle">
                Estos son los módulos activos para <strong>{{ $companyName }}</strong>. 
                Cada módulo incluye funcionalidades específicas para optimizar tu operación.
            </p>
        </div>
        
        <div class="hero-stats">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['total'] }}</div>
                <div class="stat-label">Módulos Activos</div>
            </div>
            <div class="stat-card stat-card-accent">
                <div class="stat-number">{{ $stats['active'] }}</div>
                <div class="stat-label">Funcionalidades</div>
            </div>
        </div>
    </div>

    {{-- Módulos Grid --}}
    <div class="modules-grid">
        @forelse($modules as $module)
            <div class="module-card" style="--module-color: {{ $module->color ?? '#0EA5E9' }};">
                
                {{-- Header del Card --}}
                <div class="module-header">
                    <div class="module-icon">
                        @if($module->icon)
                            {{ $module->icon }}
                        @else
                            {{ $module->detailed_description['icon'] ?? '◈' }}
                        @endif
                    </div>
                    <div class="module-status">
                        <span class="status-badge status-active">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Activo
                        </span>
                    </div>
                </div>

                {{-- Contenido --}}
                <div class="module-content">
                    <h3 class="module-title">{{ $module->name }}</h3>
                    
                    <p class="module-description">
                        @if($module->detailed_description)
                            {{ $module->detailed_description['description'] }}
                        @else
                            {{ $module->description ?? 'Módulo del sistema' }}
                        @endif
                    </p>

                    {{-- Características --}}
                    @if($module->detailed_description && isset($module->detailed_description['features']))
                        <ul class="module-features">
                            @foreach($module->detailed_description['features'] as $feature)
                                <li>
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0;">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="module-footer">
                    <span class="module-phase">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        Fase {{ $module->phase_required }}
                    </span>
                    @if($module->route_prefix)
                        <span class="module-route">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
                                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                            </svg>
                            {{ $module->route_prefix }}
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>Sin módulos activos</h3>
                <p>No hay módulos disponibles para tu cuenta. Contacta al administrador del sistema.</p>
            </div>
        @endforelse
    </div>

    {{-- Info Adicional --}}
    <div class="info-section">
        <div class="info-card">
            <div class="info-icon">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="info-content">
                <h4>¿Necesitas más módulos?</h4>
                <p>
                    Los módulos disponibles dependen de tu plan de suscripción actual. 
                    Si necesitas acceso a más funcionalidades, contacta a 
                    <strong>Soporte AMR Tech</strong> para actualizar tu plan.
                </p>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-icon info-icon-success">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="info-content">
                <h4>Soporte Incluido</h4>
                <p>
                    Todos los módulos incluyen soporte técnico básico. 
                    Los planes superiores incluyen soporte prioritario y capacitación.
                </p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
.modules-page {
    padding: var(--space-6);
    max-width: 1400px;
    margin: 0 auto;
}

{{-- Hero Section --}}
.modules-hero {
    background: linear-gradient(135deg, var(--gacov-bg-surface) 0%, var(--gacov-bg-elevated) 100%);
    border: 1px solid var(--gacov-border);
    border-radius: var(--radius-xl);
    padding: var(--space-8);
    margin-bottom: var(--space-8);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-8);
    flex-wrap: wrap;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    background: rgba(14, 165, 233, 0.1);
    color: var(--gacov-primary);
    border: 1px solid rgba(14, 165, 233, 0.2);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-full);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: var(--space-4);
}

.hero-title {
    font-size: 32px;
    font-weight: 800;
    color: var(--gacov-text-primary);
    margin: 0 0 var(--space-3) 0;
    font-family: var(--font-display);
}

.hero-subtitle {
    font-size: 16px;
    color: var(--gacov-text-secondary);
    margin: 0;
    max-width: 500px;
    line-height: 1.6;
}

.hero-stats {
    display: flex;
    gap: var(--space-4);
}

.stat-card {
    background: var(--gacov-bg-surface);
    border: 1px solid var(--gacov-border);
    border-radius: var(--radius-lg);
    padding: var(--space-5) var(--space-6);
    text-align: center;
    min-width: 120px;
}

.stat-card-accent {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
    border-color: rgba(14, 165, 233, 0.3);
}

.stat-number {
    font-size: 36px;
    font-weight: 800;
    color: var(--gacov-primary);
    font-family: var(--font-display);
    line-height: 1;
}

.stat-label {
    font-size: 12px;
    color: var(--gacov-text-muted);
    margin-top: var(--space-2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

{{-- Grid de Módulos --}}
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: var(--space-5);
    margin-bottom: var(--space-8);
}

.module-card {
    background: var(--gacov-bg-surface);
    border: 1px solid var(--gacov-border);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all 0.2s ease;
}

.module-card:hover {
    border-color: var(--module-color, var(--gacov-primary));
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--space-5);
    background: linear-gradient(135deg, color-mix(in srgb, var(--module-color, #0EA5E9) 10%, transparent) 0%, transparent 100%);
    border-bottom: 1px solid var(--gacov-border);
}

.module-icon {
    font-size: 32px;
    line-height: 1;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: var(--radius-full);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: rgba(16, 185, 129, 0.1);
    color: #10B981;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.module-content {
    padding: var(--space-5);
}

.module-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--gacov-text-primary);
    margin: 0 0 var(--space-2) 0;
}

.module-description {
    font-size: 14px;
    color: var(--gacov-text-secondary);
    margin: 0 0 var(--space-4) 0;
    line-height: 1.6;
}

.module-features {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.module-features li {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    font-size: 13px;
    color: var(--gacov-text-secondary);
}

.module-features li svg {
    color: var(--module-color, var(--gacov-primary));
    margin-top: 2px;
}

.module-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4) var(--space-5);
    background: var(--gacov-bg-elevated);
    border-top: 1px solid var(--gacov-border);
}

.module-phase,
.module-route {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: var(--gacov-text-muted);
}

{{-- Empty State --}}
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: var(--space-12);
    background: var(--gacov-bg-surface);
    border: 1px dashed var(--gacov-border);
    border-radius: var(--radius-xl);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: var(--space-4);
}

.empty-state h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--gacov-text-primary);
    margin: 0 0 var(--space-2) 0;
}

.empty-state p {
    font-size: 14px;
    color: var(--gacov-text-muted);
    margin: 0;
}

{{-- Info Section --}}
.info-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-4);
}

.info-card {
    display: flex;
    gap: var(--space-4);
    padding: var(--space-5);
    background: var(--gacov-bg-surface);
    border: 1px solid var(--gacov-border);
    border-radius: var(--radius-lg);
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-md);
    background: rgba(14, 165, 233, 0.1);
    color: var(--gacov-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-icon-success {
    background: rgba(16, 185, 129, 0.1);
    color: #10B981;
}

.info-icon svg {
    width: 20px;
    height: 20px;
}

.info-content h4 {
    font-size: 14px;
    font-weight: 700;
    color: var(--gacov-text-primary);
    margin: 0 0 var(--space-2) 0;
}

.info-content p {
    font-size: 13px;
    color: var(--gacov-text-secondary);
    margin: 0;
    line-height: 1.6;
}

{{-- Responsive --}}
@media (max-width: 768px) {
    .modules-page {
        padding: var(--space-4);
    }
    
    .modules-hero {
        flex-direction: column;
        text-align: center;
    }
    
    .hero-subtitle {
        max-width: 100%;
    }
    
    .hero-stats {
        width: 100%;
        justify-content: center;
    }
    
    .modules-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
