@extends('super-admin.layout')

@section('title', 'Proyecto y documentación')

@push('styles')
<style>
    .project-doc-shell {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: var(--space-5);
        align-items: start;
    }
    .project-doc-list {
        display: flex;
        flex-direction: column;
        gap: var(--space-3);
    }
    .project-doc-item {
        display: block;
        padding: var(--space-4);
        border-radius: var(--radius-md);
        border: 1px solid var(--gacov-border);
        background: var(--gacov-bg-surface);
        text-decoration: none;
        color: inherit;
        transition: border-color var(--transition), transform var(--transition), box-shadow var(--transition);
    }
    .project-doc-item:hover {
        border-color: rgba(0, 212, 255, .35);
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }
    .project-doc-item.is-active {
        border-color: rgba(0, 212, 255, .45);
        background: linear-gradient(180deg, rgba(0, 212, 255, .08) 0%, rgba(124, 58, 237, .04) 100%);
    }
    .project-doc-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-2);
        margin-bottom: var(--space-2);
    }
    .project-doc-category {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(59, 130, 246, .12);
        color: #60A5FA;
        font-size: 11px;
        font-weight: 700;
    }
    .project-doc-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--gacov-text-primary);
        margin-bottom: 6px;
    }
    .project-doc-excerpt {
        font-size: 12px;
        color: var(--gacov-text-secondary);
        line-height: 1.55;
    }
    .project-status-bar {
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: var(--gacov-bg-elevated);
        overflow: hidden;
    }
    .project-status-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #00D4FF 0%, #7C3AED 100%);
    }
    .project-markdown {
        font-size: 14px;
        color: var(--gacov-text-secondary);
        line-height: 1.75;
    }
    .project-markdown h1,
    .project-markdown h2,
    .project-markdown h3 {
        color: var(--gacov-text-primary);
        line-height: 1.2;
    }
    .project-markdown h1 { font-size: 28px; margin-bottom: 18px; }
    .project-markdown h2 {
        font-size: 18px;
        margin-top: 28px;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--gacov-border);
    }
    .project-markdown h3 { font-size: 15px; margin-top: 20px; margin-bottom: 8px; }
    .project-markdown p,
    .project-markdown li { color: var(--gacov-text-secondary); }
    .project-markdown ul,
    .project-markdown ol { padding-left: 22px; }
    .project-markdown code {
        font-family: var(--font-mono);
        font-size: 12px;
        background: var(--gacov-bg-elevated);
        color: var(--gacov-text-primary);
        border-radius: 8px;
        padding: 2px 6px;
    }
    .project-markdown pre {
        overflow-x: auto;
        background: #0B1120;
        border: 1px solid var(--gacov-border);
        border-radius: var(--radius-md);
        padding: 16px;
        margin: 16px 0;
    }
    .project-markdown pre code {
        background: transparent;
        padding: 0;
    }
    .project-markdown a {
        color: var(--gacov-primary);
        text-decoration: none;
    }
    .project-markdown blockquote {
        margin: 18px 0;
        padding: 12px 16px;
        border-left: 3px solid var(--gacov-primary);
        background: rgba(59, 130, 246, .08);
        border-radius: 0 var(--radius-md) var(--radius-md) 0;
    }
    @media (max-width: 1100px) {
        .project-doc-shell {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:var(--space-6);">
    <div>
        <h1 class="page-title">Centro de proyecto</h1>
        <p class="page-subtitle">Seguimiento técnico, documentación viva y control interno del avance del desarrollo.</p>
    </div>
    <a href="{{ route('super-admin.dashboard') }}" class="btn-secondary" style="text-decoration:none;">
        Volver al panel
    </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--space-4);margin-bottom:var(--space-6);">
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:30px;font-weight:700;color:var(--gacov-primary);font-family:var(--font-display);">{{ $statusSummary['progress'] }}%</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">Avance global por checklist</div>
        <div class="project-status-bar" style="margin-top:var(--space-3);">
            <span style="width: {{ max(6, $statusSummary['progress']) }}%;"></span>
        </div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:30px;font-weight:700;color:var(--gacov-success);font-family:var(--font-display);">{{ $statusSummary['completed_items'] }}</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">Ítems completados</div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:30px;font-weight:700;color:var(--gacov-warning);font-family:var(--font-display);">{{ $statusSummary['in_progress_items'] }}</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">Ítems en curso</div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:30px;font-weight:700;color:var(--gacov-error);font-family:var(--font-display);">{{ $statusSummary['pending_items'] }}</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">Pendientes críticos</div>
    </div>
    <div class="panel" style="padding:var(--space-5);">
        <div style="font-size:30px;font-weight:700;color:var(--gacov-info);font-family:var(--font-display);">{{ $statusSummary['document_count'] }}</div>
        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:var(--space-2);">Documentos vinculados</div>
        @if($statusSummary['last_updated_at'])
            <div style="margin-top:var(--space-2);font-size:11px;color:var(--gacov-text-muted);">
                Actualizado {{ $statusSummary['last_updated_at']->format('d/m/Y H:i') }}
            </div>
        @endif
    </div>
</div>

<div class="project-doc-shell">
    <aside class="project-doc-list">
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Biblioteca del proyecto</h2>
            </div>
            <div class="panel-body" style="display:flex;flex-direction:column;gap:var(--space-3);">
                @forelse($documents as $document)
                    <a href="{{ route('super-admin.project.index', ['doc' => $document['slug']]) }}"
                       class="project-doc-item {{ ($activeDocument['slug'] ?? null) === $document['slug'] ? 'is-active' : '' }}">
                        <div class="project-doc-meta">
                            <span class="project-doc-category">{{ $document['category'] }}</span>
                            <span style="font-size:11px;color:var(--gacov-text-muted);">{{ $document['modified_at']->format('d/m/Y') }}</span>
                        </div>
                        <div class="project-doc-title">{{ $document['title'] }}</div>
                        <div class="project-doc-excerpt">{{ $document['excerpt'] }}</div>
                    </a>
                @empty
                    <div style="padding:var(--space-6);text-align:center;color:var(--gacov-text-muted);font-size:13px;">
                        No hay documentos en <code>docs/</code>.
                    </div>
                @endforelse
            </div>
        </div>
    </aside>

    <section style="display:flex;flex-direction:column;gap:var(--space-4);">
        @if($activeDocument)
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">{{ $activeDocument['title'] }}</h2>
                        <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:4px;">
                            {{ $activeDocument['filename'] }} · actualizado {{ $activeDocument['modified_at']->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    @if(($activeDocument['checklists']['total'] ?? 0) > 0)
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="badge-success">{{ $activeDocument['checklists']['completed'] }} listos</span>
                            <span class="badge-warning">{{ $activeDocument['checklists']['pending'] }} pendientes</span>
                        </div>
                    @endif
                </div>
                @if(!empty($activeDocument['headings']))
                    <div class="panel-body" style="padding-bottom:0;">
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            @foreach($activeDocument['headings'] as $heading)
                                <a href="#{{ $heading['id'] }}"
                                   style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:var(--gacov-bg-elevated);color:var(--gacov-text-secondary);font-size:11px;text-decoration:none;">
                                    {{ $heading['text'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="panel-body">
                    <article class="project-markdown">
                        {!! preg_replace_callback('/<(h[23])>(.*?)<\\/\\1>/', static function (array $matches): string {
                            return '<' . $matches[1] . ' id="' . \Illuminate\Support\Str::slug(strip_tags($matches[2])) . '">' . $matches[2] . '</' . $matches[1] . '>';
                        }, $activeDocument['html']) !!}
                    </article>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
