@extends('layouts.app')

@section('title', 'Exportación WorldOffice')

@section('content')
<div class="inventory-shell inventory-shell--light worldoffice-shell">
    <section class="inventory-hero">
        <div class="inventory-hero__grid">
            <div>
                <span class="inventory-hero__eyebrow">WorldOffice</span>
                <h1 class="inventory-hero__title">Descargas de exportación</h1>
                <p class="inventory-hero__subtitle">
                    Genera los formatos de carga y descarga para bodega principal, vehículos / rutas y máquinas.
                    El acceso está habilitado para admin, manager y contador, y bloqueado para conductor.
                </p>
                <div class="inventory-hero__badges">
                    <span class="badge badge-info">6 formatos disponibles</span>
                    <span class="badge badge-neutral">WorldOffice contable</span>
                    <span class="badge badge-success">Formato estándar</span>
                </div>
            </div>
        </div>
    </section>

    <section class="worldoffice-grid">
        <article class="worldoffice-card">
            <div class="worldoffice-card__eyebrow">Bodega principal</div>
            <h2 class="worldoffice-card__title">Formato de carga y descarga</h2>
            <p class="worldoffice-card__copy">Exporta el inventario central con stock actual y columnas listas para registrar entrada o salida.</p>
            <div class="worldoffice-card__actions">
                <a href="{{ route('worldoffice.download', ['category' => 'bodega', 'direction' => 'load']) }}" class="btn btn-primary btn-sm">Descargar carga</a>
                <a href="{{ route('worldoffice.download', ['category' => 'bodega', 'direction' => 'unload']) }}" class="btn btn-secondary btn-sm">Descargar descarga</a>
            </div>
        </article>

        <article class="worldoffice-card">
            <div class="worldoffice-card__eyebrow">Vehículos / rutas</div>
            <h2 class="worldoffice-card__title">Formato de carga y descarga</h2>
            <p class="worldoffice-card__copy">Lista las rutas activas con su vehículo y los productos que deben cargarse o descargarse.</p>
            <div class="worldoffice-card__actions">
                <a href="{{ route('worldoffice.download', ['category' => 'routes', 'direction' => 'load']) }}" class="btn btn-primary btn-sm">Descargar carga</a>
                <a href="{{ route('worldoffice.download', ['category' => 'routes', 'direction' => 'unload']) }}" class="btn btn-secondary btn-sm">Descargar descarga</a>
            </div>
        </article>

        <article class="worldoffice-card">
            <div class="worldoffice-card__eyebrow">Máquinas</div>
            <h2 class="worldoffice-card__title">Formato de carga y descarga</h2>
            <p class="worldoffice-card__copy">Consolida todas las máquinas en un único archivo con el detalle de stock por producto.</p>
            <div class="worldoffice-card__actions">
                <a href="{{ route('worldoffice.download', ['category' => 'machines', 'direction' => 'load']) }}" class="btn btn-primary btn-sm">Descargar carga</a>
                <a href="{{ route('worldoffice.download', ['category' => 'machines', 'direction' => 'unload']) }}" class="btn btn-secondary btn-sm">Descargar descarga</a>
            </div>
        </article>
    </section>

    <section class="panel" style="margin-top:var(--space-6)">
        <div class="panel-header">
            <span class="panel-title">Cobertura de acceso</span>
        </div>
        <div class="panel-body">
            <div class="inventory-results-bar">
                <span><strong>Admin</strong>, <strong>Manager</strong> y <strong>Contador</strong> pueden descargar estos formatos.</span>
                <span><strong>Conductor</strong> no tiene acceso a esta sección.</span>
            </div>
        </div>
    </section>
</div>
@endsection
