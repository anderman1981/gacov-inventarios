@extends('layouts.app')

@section('title', 'Calendario operativo de rutas')

@php
    $previousWeek = $weekStart->copy()->subWeek()->toDateString();
    $nextWeek = $weekStart->copy()->addWeek()->toDateString();
    $dayLabels = $calendarDays->pluck('date');
    $visibleRouteCount = $calendarDays->sum(fn (array $calendarDay): int => $calendarDay['unassigned_routes']->count() + collect($calendarDay['driver_routes'])->flatten(1)->count());
@endphp

@section('content')
<div class="page-header page-header--compact">
    <div>
        <h1 class="page-title">Calendario operativo de rutas</h1>
        <p class="page-subtitle">Programa coberturas, festivos y rotaciones diarias sin cambiar la asignación base de cada conductor.</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('operations.routes.board') }}" class="btn" style="width:auto;background:var(--gacov-bg-elevated);color:var(--gacov-text-primary)">
            Ver asignación base
        </a>
    </div>
</div>

<div class="route-calendar__summary">
    <div class="route-calendar__summary-copy">
        <strong>Cómo funciona.</strong>
        Cada tarjeta representa la ruta efectiva de ese día. Puedes arrastrarla entre conductores o dejarla libre. Si una ruta vuelve a su conductor base, el sistema limpia la programación especial y conserva la base.
    </div>
    <div class="route-calendar__summary-stats">
        <span class="badge badge-info">{{ $conductors->count() }} conductores</span>
        <span class="badge badge-neutral">{{ $dayLabels->count() }} días</span>
        <span class="badge badge-success">{{ $visibleRouteCount }} rutas visibles</span>
    </div>
</div>

<div class="route-calendar__toolbar">
    <a href="{{ route('operations.routes.calendar', ['week_start' => $previousWeek]) }}" class="btn route-calendar__nav-btn" style="width:auto">
        ← Semana anterior
    </a>
    <div class="route-calendar__period">
        Semana del {{ $weekStart->translatedFormat('d \\d\\e F \\d\\e Y') }}
    </div>
    <a href="{{ route('operations.routes.calendar', ['week_start' => $nextWeek]) }}" class="btn route-calendar__nav-btn" style="width:auto">
        Semana siguiente →
    </a>
</div>

<div class="route-calendar-week" data-route-calendar-board>
    <div class="route-calendar-week__scroll">
    <div class="route-calendar-week__row route-calendar-week__row--header">
        <div class="route-calendar-week__corner">
            <span>Conductor</span>
            <small>Arrastra a cualquier día</small>
        </div>

        @foreach($calendarDays as $calendarDay)
        <div class="route-calendar-week__day">
            <div class="route-calendar-week__day-name">{{ ucfirst($calendarDay['date']->translatedFormat('l')) }}</div>
            <div class="route-calendar-week__day-date">{{ $calendarDay['date']->translatedFormat('d \\d\\e F') }}</div>
        </div>
        @endforeach
    </div>

    <div class="route-calendar-week__row route-calendar-week__row--unassigned">
        <div class="route-calendar-week__label">
            <div class="route-calendar-week__label-title">Sin asignar</div>
            <div class="route-calendar-week__label-copy">Cobertura abierta para la semana.</div>
        </div>

        @foreach($calendarDays as $calendarDay)
        @php($dateValue = $calendarDay['date']->toDateString())
        <section
            class="route-calendar-week__cell route-calendar-week__cell--unassigned"
            data-route-calendar-dropzone
            data-target-driver-id=""
            data-assignment-date="{{ $dateValue }}">
            <div class="route-calendar-week__cell-head">
                <span class="badge badge-neutral">{{ $calendarDay['unassigned_routes']->count() }}</span>
            </div>

            <div class="route-calendar-week__cards">
                @forelse($calendarDay['unassigned_routes'] as $routeCard)
                @php($route = $routeCard['route'])
                <article class="route-card route-card--compact" draggable="true" data-route-card data-route-id="{{ $route->id }}">
                    <div class="route-card__eyebrow">{{ $route->code }}</div>
                    <div class="route-card__title">{{ $route->name }}</div>
                    <div class="route-card__meta">
                        <span>{{ $route->vehicle_plate ?: 'Sin placa' }}</span>
                        <span>{{ $route->machines_count }} máquina(s)</span>
                        <span class="badge {{ $routeCard['is_override'] ? 'badge-warning' : 'badge-neutral' }}">{{ $routeCard['source_label'] }}</span>
                    </div>
                </article>
                @empty
                <div class="route-calendar-week__empty">Libre</div>
                @endforelse
            </div>
        </section>
        @endforeach
    </div>

    @foreach($conductors as $conductor)
    <div class="route-calendar-week__row">
        <div class="route-calendar-week__label">
            <div class="route-calendar-week__label-title">{{ $conductor->name }}</div>
            <div class="route-calendar-week__label-copy">{{ $conductor->email }}</div>
        </div>

        @foreach($calendarDays as $calendarDay)
        @php(
            $driverRoutes = collect(data_get($calendarDay['driver_routes'], $conductor->id, collect()))
        )
        @php($dateValue = $calendarDay['date']->toDateString())
        <section
            class="route-calendar-week__cell"
            data-route-calendar-dropzone
            data-target-driver-id="{{ $conductor->id }}"
            data-assignment-date="{{ $dateValue }}">
            <div class="route-calendar-week__cell-head">
                <span class="badge {{ $driverRoutes->isNotEmpty() ? 'badge-success' : 'badge-neutral' }}">
                    {{ $driverRoutes->count() }}
                </span>
            </div>

            <div class="route-calendar-week__cards">
                @forelse($driverRoutes as $routeCard)
                @php($route = $routeCard['route'])
                <article class="route-card route-card--compact" draggable="true" data-route-card data-route-id="{{ $route->id }}">
                    <div class="route-card__eyebrow">{{ $route->code }}</div>
                    <div class="route-card__title">{{ $route->name }}</div>
                    <div class="route-card__meta">
                        <span>{{ $route->vehicle_plate ?: 'Sin placa' }}</span>
                        <span>{{ $route->machines_count }} máquina(s)</span>
                        <span class="badge {{ $routeCard['is_override'] ? 'badge-warning' : 'badge-neutral' }}">{{ $routeCard['source_label'] }}</span>
                    </div>
                </article>
                @empty
                <div class="route-calendar-week__empty">Suelta aquí una ruta</div>
                @endforelse
            </div>
        </section>
        @endforeach
    </div>
    @endforeach
    </div>
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
        card.addEventListener('dragstart', (event) => {
            draggedRouteId = card.dataset.routeId ?? null;
            draggedFromLane = card.closest('[data-route-calendar-dropzone]');
            card.classList.add('route-card--dragging');

            if (event.dataTransfer instanceof DataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', draggedRouteId ?? '');
            }
        });

        card.addEventListener('dragend', () => {
            draggedRouteId = null;
            draggedFromLane = null;
            card.classList.remove('route-card--dragging');
            board.querySelectorAll('[data-route-calendar-dropzone]').forEach((lane) => lane.classList.remove('route-calendar-week__cell--over'));
        });
    });

    board.querySelectorAll('[data-route-calendar-dropzone]').forEach((lane) => {
        lane.addEventListener('dragenter', (event) => {
            event.preventDefault();
            lane.classList.add('route-calendar-week__cell--over');
        });

        lane.addEventListener('dragover', (event) => {
            event.preventDefault();
            lane.classList.add('route-calendar-week__cell--over');
        });

        lane.addEventListener('dragleave', (event) => {
            if (event.currentTarget === event.target) {
                lane.classList.remove('route-calendar-week__cell--over');
            }
        });

        lane.addEventListener('drop', (event) => {
            event.preventDefault();
            lane.classList.remove('route-calendar-week__cell--over');

            const droppedRouteId = draggedRouteId || event.dataTransfer?.getData('text/plain') || null;

            if (!droppedRouteId || !(form instanceof HTMLFormElement) || !(routeInput instanceof HTMLInputElement) || !(driverInput instanceof HTMLInputElement) || !(dateInput instanceof HTMLInputElement)) {
                return;
            }

            if (draggedFromLane === lane) {
                return;
            }

            routeInput.value = droppedRouteId;
            driverInput.value = lane.dataset.targetDriverId ?? '';
            dateInput.value = lane.dataset.assignmentDate ?? '';
            form.submit();
        });
    });
});
</script>
@endpush
