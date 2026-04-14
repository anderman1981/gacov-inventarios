@extends('layouts.app')

@section('title', 'Rutas y conductores')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
    <div>
        <h1 class="page-title">Rutas y conductores</h1>
        <p class="page-subtitle">Reasigna rutas entre conductores con drag and drop para cubrir rotaciones, festivos o ausencias.</p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        <a href="{{ route('operations.routes.calendar') }}" class="btn btn-primary" style="width:auto">
            Calendario operativo
        </a>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom:var(--space-6)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-3a1 1 0 10-2 0 1 1 0 002 0zm-2 3a1 1 0 000 2v2a1 1 0 102 0v-2a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
    <div>
        <strong>Cómo funciona.</strong>
        Arrastra una ruta hacia otro conductor para moverla. Si el conductor destino ya tiene una ruta activa, el sistema hará el intercambio automáticamente.
    </div>
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
