@extends('layouts.app')

@section('title', 'Calendario operativo de rutas')

@php
    $previousWeek = $weekStart->copy()->subWeek()->toDateString();
    $nextWeek = $weekStart->copy()->addWeek()->toDateString();
@endphp

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
    <div>
        <h1 class="page-title">Calendario operativo de rutas</h1>
        <p class="page-subtitle">Programa coberturas, festivos y rotaciones diarias sin cambiar la asignación base de cada conductor.</p>
    </div>
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        <a href="{{ route('operations.routes.board') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
            Ver asignación base
        </a>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom:var(--space-6)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-3a1 1 0 10-2 0 1 1 0 002 0zm-2 3a1 1 0 000 2v2a1 1 0 102 0v-2a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
    <div>
        <strong>Cómo funciona.</strong>
        Cada tarjeta representa la ruta efectiva de ese día. Puedes arrastrarla entre conductores o dejarla libre. Si una ruta vuelve a su conductor base, el sistema limpia la programación especial y conserva la base.
    </div>
</div>

<div class="route-calendar__toolbar">
    <a href="{{ route('operations.routes.calendar', ['week_start' => $previousWeek]) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
        ← Semana anterior
    </a>
    <div class="route-calendar__period">
        Semana del {{ $weekStart->translatedFormat('d \\d\\e F \\d\\e Y') }}
    </div>
    <a href="{{ route('operations.routes.calendar', ['week_start' => $nextWeek]) }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
        Semana siguiente →
    </a>
</div>

<div class="route-calendar" data-route-calendar-board>
    @foreach($calendarDays as $calendarDay)
    @php($dateValue = $calendarDay['date']->toDateString())
    <section class="route-calendar__day">
        <div class="route-calendar__day-header">
            <div>
                <div class="route-calendar__day-title">{{ ucfirst($calendarDay['date']->translatedFormat('l')) }}</div>
                <div class="route-calendar__day-copy">{{ $calendarDay['date']->translatedFormat('d \\d\\e F') }}</div>
            </div>
            <span class="badge badge-info">{{ $conductors->count() }} conductores</span>
        </div>

        <div class="route-calendar__lanes">
            <section
                class="route-board__lane route-board__lane--unassigned"
                data-route-calendar-dropzone
                data-target-driver-id=""
                data-assignment-date="{{ $dateValue }}">
                <div class="route-board__lane-header">
                    <div>
                        <div class="route-board__lane-title">Sin asignar</div>
                        <div class="route-board__lane-copy">Cobertura abierta para esta fecha.</div>
                    </div>
                    <span class="badge badge-neutral">{{ $calendarDay['unassigned_routes']->count() }}</span>
                </div>

                <div class="route-board__cards">
                    @forelse($calendarDay['unassigned_routes'] as $routeCard)
                    @php($route = $routeCard['route'])
                    <article class="route-card" draggable="true" data-route-card data-route-id="{{ $route->id }}">
                        <div class="route-card__eyebrow">{{ $route->code }}</div>
                        <div class="route-card__title">{{ $route->name }}</div>
                        <div class="route-card__meta">
                            <span>{{ $route->vehicle_plate ?: 'Sin placa' }}</span>
                            <span>{{ $route->machines_count }} máquina(s)</span>
                            <span class="badge {{ $routeCard['is_override'] ? 'badge-warning' : 'badge-neutral' }}">{{ $routeCard['source_label'] }}</span>
                        </div>
                    </article>
                    @empty
                    <div class="route-board__empty">No hay rutas libres para este día.</div>
                    @endforelse
                </div>
            </section>

            @foreach($conductors as $conductor)
            @php($driverRoutes = $calendarDay['driver_routes'][$conductor->id] ?? collect())
            <section
                class="route-board__lane"
                data-route-calendar-dropzone
                data-target-driver-id="{{ $conductor->id }}"
                data-assignment-date="{{ $dateValue }}">
                <div class="route-board__lane-header">
                    <div>
                        <div class="route-board__lane-title">{{ $conductor->name }}</div>
                        <div class="route-board__lane-copy">{{ $conductor->email }}</div>
                    </div>
                    <span class="badge {{ $driverRoutes->isNotEmpty() ? 'badge-success' : 'badge-neutral' }}">
                        {{ $driverRoutes->count() }} ruta{{ $driverRoutes->count() === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="route-board__cards">
                    @forelse($driverRoutes as $routeCard)
                    @php($route = $routeCard['route'])
                    <article class="route-card" draggable="true" data-route-card data-route-id="{{ $route->id }}">
                        <div class="route-card__eyebrow">{{ $route->code }}</div>
                        <div class="route-card__title">{{ $route->name }}</div>
                        <div class="route-card__meta">
                            <span>{{ $route->vehicle_plate ?: 'Sin placa' }}</span>
                            <span>{{ $route->machines_count }} máquina(s)</span>
                            <span class="badge {{ $routeCard['is_override'] ? 'badge-warning' : 'badge-neutral' }}">{{ $routeCard['source_label'] }}</span>
                        </div>
                    </article>
                    @empty
                    <div class="route-board__empty">Suelta aquí una ruta para programarla con este conductor.</div>
                    @endforelse
                </div>
            </section>
            @endforeach
        </div>
    </section>
    @endforeach
</div>

<form id="route-calendar-form" method="POST" action="{{ route('operations.routes.calendar.store') }}" style="display:none">
    @csrf
    <input type="hidden" name="route_id" id="route-calendar-route-id">
    <input type="hidden" name="target_driver_id" id="route-calendar-driver-id">
    <input type="hidden" name="assignment_date" id="route-calendar-assignment-date">
    <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const board = document.querySelector('[data-route-calendar-board]');

    if (!board) {
        return;
    }

    const form = document.getElementById('route-calendar-form');
    const routeInput = document.getElementById('route-calendar-route-id');
    const driverInput = document.getElementById('route-calendar-driver-id');
    const dateInput = document.getElementById('route-calendar-assignment-date');
    let draggedRouteId = null;
    let draggedFromLane = null;

    board.querySelectorAll('[data-route-card]').forEach((card) => {
        card.addEventListener('dragstart', () => {
            draggedRouteId = card.dataset.routeId ?? null;
            draggedFromLane = card.closest('[data-route-calendar-dropzone]');
            card.classList.add('route-card--dragging');
        });

        card.addEventListener('dragend', () => {
            draggedRouteId = null;
            draggedFromLane = null;
            card.classList.remove('route-card--dragging');
            board.querySelectorAll('[data-route-calendar-dropzone]').forEach((lane) => lane.classList.remove('route-board__lane--over'));
        });
    });

    board.querySelectorAll('[data-route-calendar-dropzone]').forEach((lane) => {
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

            if (!draggedRouteId || !(form instanceof HTMLFormElement) || !(routeInput instanceof HTMLInputElement) || !(driverInput instanceof HTMLInputElement) || !(dateInput instanceof HTMLInputElement)) {
                return;
            }

            if (draggedFromLane === lane) {
                return;
            }

            routeInput.value = draggedRouteId;
            driverInput.value = lane.dataset.targetDriverId ?? '';
            dateInput.value = lane.dataset.assignmentDate ?? '';
            form.submit();
        });
    });
});
</script>
@endpush
