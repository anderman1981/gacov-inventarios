@extends('layouts.app')

@section('title', 'Rutas y conductores')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
    <div>
        <h1 class="page-title">Rutas y conductores</h1>
        <p class="page-subtitle">Reasigna rutas entre conductores con drag and drop para cubrir rotaciones, festivos o ausencias.</p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        <a href="{{ route('operations.routes.create') }}" class="btn btn-primary" style="width:auto">
            Añadir ruta
        </a>
        <a href="{{ route('operations.routes.calendar') }}" class="btn btn-primary" style="width:auto">
            Calendario operativo
        </a>
    </div>
</div>

<div class="inventory-command-bar">
    <div class="inventory-command-bar__meta">
        <span class="badge badge-info">Drag & drop activo</span>
        <span class="badge badge-neutral">Intercambio automático</span>
        <span class="badge badge-neutral">Sin asignar disponible</span>
    </div>
    <div class="badge badge-neutral">Arrastra una ruta para moverla entre conductores</div>
</div>

<div class="route-board" data-route-assignment-board>
    <section class="route-board__lane route-board__lane--unassigned" data-route-dropzone data-target-driver-id="">
        <div class="route-board__lane-header">
            <div>
                <div class="route-board__lane-title">Sin asignar</div>
                <div class="route-board__lane-copy">Rutas libres para cobertura o rotación.</div>
            </div>
            <span class="badge badge-neutral">{{ $unassignedRoutes->count() }}</span>
        </div>

        <div class="route-board__cards">
            @forelse($unassignedRoutes as $route)
            <article class="route-card" draggable="true" data-route-card data-route-id="{{ $route->id }}">
                <div class="route-card__eyebrow">{{ $route->code }}</div>
                <div class="route-card__title">{{ $route->name }}</div>
                <div class="route-card__meta">
                    <span>{{ $route->vehicle_plate ?: 'Sin placa' }}</span>
                    <span>{{ $route->machines_count }} máquina(s)</span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                    <a href="{{ route('operations.routes.edit', $route) }}" class="amr-icon-button amr-icon-button--primary amr-tooltip-trigger" data-tooltip="Editar ruta" aria-label="Editar ruta">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3a2 2 0 012.828 0l.586.586a2 2 0 010 2.828l-8.293 8.293a1 1 0 01-.465.263l-3.5.875a1 1 0 01-1.213-1.213l.875-3.5a1 1 0 01.263-.465L13.586 3zM12 5.414L7.5 9.914 7.086 11.5l1.586-.414L13.172 6l-1.172-1.172z"/></svg>
                    </a>
                    <form method="POST" action="{{ route('operations.routes.destroy', $route) }}" onsubmit="return confirm('¿Quieres quitar esta ruta del sistema?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="amr-icon-button amr-icon-button--danger amr-tooltip-trigger" data-tooltip="Quitar ruta" aria-label="Quitar ruta">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 2a2 2 0 00-2 2v1H4a1 1 0 100 2h.5l.8 9.2A2 2 0 007.3 18h5.4a2 2 0 001.99-1.8L15.5 7H16a1 1 0 100-2h-2V4a2 2 0 00-2-2H8zm2 3a1 1 0 10-2 0v1h2V5zm-2 4a1 1 0 012 0v5a1 1 0 11-2 0V9z" clip-rule="evenodd"/></svg>
                        </button>
                    </form>
                </div>
            </article>
            @empty
            <div class="route-board__empty">No hay rutas libres en este momento.</div>
            @endforelse
        </div>
    </section>

    @foreach($conductors as $conductor)
    @php($assignedRoute = $assignedRoutesByDriver[$conductor->id] ?? null)
    <section class="route-board__lane" data-route-dropzone data-target-driver-id="{{ $conductor->id }}">
        <div class="route-board__lane-header">
            <div>
                <div class="route-board__lane-title">{{ $conductor->name }}</div>
                <div class="route-board__lane-copy">{{ $conductor->email }}</div>
            </div>
            <span class="badge {{ $assignedRoute ? 'badge-success' : 'badge-neutral' }}">
                {{ $assignedRoute ? 'Con ruta' : 'Disponible' }}
            </span>
        </div>

        <div class="route-board__cards">
            @if($assignedRoute)
            <article class="route-card" draggable="true" data-route-card data-route-id="{{ $assignedRoute->id }}">
                <div class="route-card__eyebrow">{{ $assignedRoute->code }}</div>
                <div class="route-card__title">{{ $assignedRoute->name }}</div>
                <div class="route-card__meta">
                    <span>{{ $assignedRoute->vehicle_plate ?: 'Sin placa' }}</span>
                    <span>{{ $assignedRoute->machines_count }} máquina(s)</span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                    <a href="{{ route('operations.routes.edit', $assignedRoute) }}" class="amr-icon-button amr-icon-button--primary amr-tooltip-trigger" data-tooltip="Editar ruta" aria-label="Editar ruta">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3a2 2 0 012.828 0l.586.586a2 2 0 010 2.828l-8.293 8.293a1 1 0 01-.465.263l-3.5.875a1 1 0 01-1.213-1.213l.875-3.5a1 1 0 01.263-.465L13.586 3zM12 5.414L7.5 9.914 7.086 11.5l1.586-.414L13.172 6l-1.172-1.172z"/></svg>
                    </a>
                    <form method="POST" action="{{ route('operations.routes.destroy', $assignedRoute) }}" onsubmit="return confirm('¿Quieres quitar esta ruta del sistema?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="amr-icon-button amr-icon-button--danger amr-tooltip-trigger" data-tooltip="Quitar ruta" aria-label="Quitar ruta">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 2a2 2 0 00-2 2v1H4a1 1 0 100 2h.5l.8 9.2A2 2 0 007.3 18h5.4a2 2 0 001.99-1.8L15.5 7H16a1 1 0 100-2h-2V4a2 2 0 00-2-2H8zm2 3a1 1 0 10-2 0v1h2V5zm-2 4a1 1 0 012 0v5a1 1 0 11-2 0V9z" clip-rule="evenodd"/></svg>
                        </button>
                    </form>
                </div>
            </article>
            @else
            <div class="route-board__empty">Suelta aquí una ruta para asignarla a este conductor.</div>
            @endif
        </div>
    </section>
    @endforeach
</div>

<form id="route-reassign-form" method="POST" action="{{ route('operations.routes.reassign') }}" style="display:none">
    @csrf
    <input type="hidden" name="route_id" id="route-reassign-route-id">
    <input type="hidden" name="target_driver_id" id="route-reassign-driver-id">
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const board = document.querySelector('[data-route-assignment-board]');

    if (!board) {
        return;
    }

    const form = document.getElementById('route-reassign-form');
    const routeInput = document.getElementById('route-reassign-route-id');
    const driverInput = document.getElementById('route-reassign-driver-id');
    let draggedRouteId = null;
    let draggedFromLane = null;

    board.querySelectorAll('[data-route-card]').forEach((card) => {
        card.addEventListener('dragstart', () => {
            draggedRouteId = card.dataset.routeId ?? null;
            draggedFromLane = card.closest('[data-route-dropzone]');
            card.classList.add('route-card--dragging');
        });

        card.addEventListener('dragend', () => {
            draggedRouteId = null;
            draggedFromLane = null;
            card.classList.remove('route-card--dragging');
            board.querySelectorAll('[data-route-dropzone]').forEach((lane) => lane.classList.remove('route-board__lane--over'));
        });
    });

    board.querySelectorAll('[data-route-dropzone]').forEach((lane) => {
        lane.addEventListener('dragover', (event) => {
            event.preventDefault();
            lane.classList.add('route-board__lane--over');
        });

        lane.addEventListener('dragleave', (event) => {
            if (event.currentTarget === event.target) {
                lane.classList.remove('route-board__lane--over');
            }
        });

        lane.addEventListener('drop', (event) => {
            event.preventDefault();
            lane.classList.remove('route-board__lane--over');

            if (!draggedRouteId || !(form instanceof HTMLFormElement) || !(routeInput instanceof HTMLInputElement) || !(driverInput instanceof HTMLInputElement)) {
                return;
            }

            if (draggedFromLane === lane) {
                return;
            }

            routeInput.value = draggedRouteId;
            driverInput.value = lane.dataset.targetDriverId ?? '';
            form.submit();
        });
    });
});
</script>
@endpush
