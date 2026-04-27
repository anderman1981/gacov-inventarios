@php
    $headline = $projectAudit['headline'];
@endphp

<div
    class="project-audit-widget"
    x-data="{ open: false }"
    x-effect="document.body.classList.toggle('project-audit-open', open); if (open) { $nextTick(() => $refs.closeButton?.focus()) }"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="project-audit-fab"
        aria-controls="project-audit-modal"
        :aria-expanded="open.toString()"
        @click="open = true"
    >
        <span class="project-audit-fab__icon">📊</span>
        <span>Estado del proyecto</span>
    </button>

    <div
        id="project-audit-modal"
        class="project-audit-modal"
        x-show="open"
        x-cloak
        x-transition.opacity.duration.220ms
        role="dialog"
        aria-modal="true"
        aria-labelledby="project-audit-title"
    >
        <div class="project-audit-modal__backdrop" @click="open = false"></div>

        <div class="project-audit-modal__panel" @click.stop>
            <header class="project-audit-modal__header">
                <div>
                    <div class="project-audit-modal__label">{{ $projectAudit['label'] }}</div>
                    <h2 id="project-audit-title" class="project-audit-modal__title">{{ $projectAudit['title'] }}</h2>
                    <p class="project-audit-modal__subtitle">{{ $projectAudit['subtitle'] }}</p>
                </div>

                <button
                    type="button"
                    class="project-audit-modal__close"
                    @click="open = false"
                    x-ref="closeButton"
                    aria-label="Cerrar reporte de estado"
                >
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </header>

            <div class="project-audit-modal__body">
                <section class="project-audit-summary">
                    <div class="project-audit-summary__label">Resumen ejecutivo del proyecto</div>

                    <div class="project-audit-summary__grid">
                        @foreach($projectAudit['summary'] as $card)
                            <article class="project-audit-summary__card project-audit-summary__card--{{ $card['tone'] }}">
                                <div class="project-audit-summary__icon">{{ $card['icon'] }}</div>
                                <div class="project-audit-summary__value">{{ $card['value'] }}</div>
                                <div class="project-audit-summary__caption">{{ $card['label'] }}</div>
                            </article>
                        @endforeach
                    </div>

                    <p class="project-audit-summary__text">
                        {{ $headline['lead'] }}
                        <span class="is-success">{{ $headline['success'] }}</span>
                        <span class="is-warning">{{ $headline['warning'] }}</span>
                        <span class="is-muted">{{ $headline['muted'] }}</span>
                    </p>

                    <div class="project-audit-evidence">
                        @foreach($projectAudit['evidence'] as $evidence)
                            <div class="project-audit-evidence__pill">
                                <span class="project-audit-evidence__value">{{ $evidence['value'] }}</span>
                                <span class="project-audit-evidence__label">{{ $evidence['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                @foreach($projectAudit['sections'] as $section)
                    <section class="project-audit-section">
                        <div class="project-audit-section__title">{{ $section['title'] }}</div>

                        <div class="project-audit-entry-list">
                            @foreach($section['items'] as $item)
                                <article class="project-audit-entry project-audit-entry--{{ $item['state'] }}">
                                    <div class="project-audit-entry__headline">
                                        <span class="project-audit-entry__badge project-audit-entry__badge--{{ $item['state'] }}">
                                            {{ $item['badge'] }}
                                        </span>
                                        <h3>{{ $item['title'] }}</h3>
                                    </div>
                                    <p>{{ $item['description'] }}</p>
                                    <div class="project-audit-entry__meta">{{ $item['meta'] }}</div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</div>
