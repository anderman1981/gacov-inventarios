@extends('layouts.app')
@section('title', 'Cargar vehículo — Fase 2')

@section('content')
@php
    $routeQuery = $record->route_id ? ['route_id' => $record->route_id] : [];
@endphp

<div class="page-header">
    <h1 class="page-title">Cargar el vehículo</h1>
    <p class="page-subtitle">
        <a href="{{ route('driver.dashboard', $routeQuery) }}" style="color:var(--gacov-text-muted);text-decoration:none">Mi ruta</a>
        / Surtido <strong>{{ $record->code }}</strong> — Fase 2 de 3
    </p>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:var(--space-5)">
    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- Progress stepper --}}
<div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-6);flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:var(--space-2)">
        <span style="width:28px;height:28px;border-radius:50%;background:var(--gacov-success);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700">✓</span>
        <span style="font-size:13px;color:var(--gacov-success);font-weight:600">Inspección</span>
    </div>
    <span style="color:var(--gacov-border);font-size:18px">→</span>
    <div style="display:flex;align-items:center;gap:var(--space-2)">
        <span style="width:28px;height:28px;border-radius:50%;background:var(--gacov-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700">2</span>
        <span style="font-size:13px;color:var(--gacov-primary);font-weight:600">Cargar vehículo</span>
    </div>
    <span style="color:var(--gacov-border);font-size:18px">→</span>
    <div style="display:flex;align-items:center;gap:var(--space-2)">
        <span style="width:28px;height:28px;border-radius:50%;background:var(--gacov-bg-elevated);color:var(--gacov-text-muted);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:1px solid var(--gacov-border)">3</span>
        <span style="font-size:13px;color:var(--gacov-text-muted)">Surtir máquina</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:var(--space-5);align-items:start" class="stocking-loading-layout">

    {{-- Lista de productos a cargar --}}
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">Lista de carga — {{ $record->machine?->name }}</span>
            <span class="badge badge-info">{{ $items->count() }} producto{{ $items->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="panel-body" style="padding:0">
            <div class="alert alert-warning" style="margin:var(--space-4);border-radius:var(--radius-md)">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>
                    <strong>Carga exactamente estas cantidades</strong> en canastas o cajas por máquina.
                    Cuando termines, confirma con el botón a la derecha.
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Unidad</th>
                        <th style="text-align:center;width:120px">Cantidad</th>
                        <th style="text-align:center;width:80px">Listo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr class="loading-item-row" data-row-index="{{ $loop->index }}">
                        <td>
                            <strong>{{ $item->product?->name ?? "Producto #{$item->product_id}" }}</strong>
                            @if($item->product?->code)
                            <div style="font-size:11px;color:var(--gacov-text-muted);margin-top:2px">{{ strtoupper($item->product->code) }}</div>
                            @endif
                        </td>
                        <td style="color:var(--gacov-text-muted)">{{ $item->product?->unit ?? '—' }}</td>
                        <td style="text-align:center">
                            <span class="badge badge-info" style="font-size:15px;font-weight:700;padding:6px 14px">
                                {{ number_format($item->quantity_loaded, 0, ',', '.') }}
                            </span>
                        </td>
                        <td style="text-align:center">
                            <button type="button"
                                    class="btn-check-item"
                                    data-index="{{ $loop->index }}"
                                    style="width:32px;height:32px;border-radius:50%;border:2px solid var(--gacov-border);background:transparent;cursor:pointer;font-size:16px;transition:all var(--transition-fast)"
                                    title="Marcar como cargado">
                                ○
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--gacov-text-muted);padding:var(--space-6)">
                            No hay productos en este surtido.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Panel de confirmación --}}
    <div style="position:sticky;top:var(--space-4)">
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Confirmar carga</span>
            </div>
            <div class="panel-body">
                <div style="margin-bottom:var(--space-4)">
                    <div style="font-size:13px;color:var(--gacov-text-muted);margin-bottom:var(--space-2)">Máquina</div>
                    <div style="font-weight:600">{{ $record->machine?->name }}</div>
                    @if($record->machine?->location)
                    <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:2px">{{ $record->machine->location }}</div>
                    @endif
                </div>

                <div style="margin-bottom:var(--space-4)">
                    <div style="font-size:13px;color:var(--gacov-text-muted);margin-bottom:var(--space-2)">Código de surtido</div>
                    <div style="font-family:var(--font-mono);font-size:13px;color:var(--gacov-primary)">{{ $record->code }}</div>
                </div>

                <div style="margin-bottom:var(--space-5)">
                    <div style="font-size:13px;color:var(--gacov-text-muted);margin-bottom:var(--space-2)">Progreso de carga</div>
                    <div style="background:var(--gacov-bg-elevated);border-radius:var(--radius-full);height:8px;overflow:hidden">
                        <div id="loading-progress-bar" style="background:var(--gacov-primary);height:100%;width:0%;transition:width var(--transition-base);border-radius:var(--radius-full)"></div>
                    </div>
                    <div style="font-size:12px;color:var(--gacov-text-muted);margin-top:6px;text-align:center">
                        <span id="loading-progress-text">0 / {{ $items->count() }} productos marcados</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('driver.stocking.confirm-load', $record) }}">
                    @csrf
                    <button type="submit"
                            id="btn-confirm-load"
                            class="btn btn-primary"
                            style="width:100%;font-size:15px;padding:12px"
                            onclick="return confirm('¿Confirmás que ya cargaste todos los productos en el vehículo?')">
                        ✓ Ya cargué todo
                    </button>
                </form>

                <a href="{{ route('driver.dashboard', $routeQuery) }}"
                   style="display:block;text-align:center;margin-top:var(--space-3);font-size:13px;color:var(--gacov-text-muted);text-decoration:none">
                    Volver al dashboard
                </a>
            </div>
        </div>

        @if($record->notes)
        <div class="panel" style="margin-top:var(--space-3)">
            <div class="panel-body">
                <div style="font-size:12px;color:var(--gacov-text-muted);margin-bottom:4px;font-weight:600">Observaciones</div>
                <div style="font-size:13px">{{ $record->notes }}</div>
            </div>
        </div>
        @endif
    </div>

</div>

<style>
@media (max-width: 768px) {
    .stocking-loading-layout {
        grid-template-columns: 1fr !important;
    }
}
.loading-item-row.is-checked td { opacity: .5; text-decoration: none; }
.loading-item-row.is-checked .badge-info { background: var(--gacov-success) !important; }
.btn-check-item.is-checked {
    border-color: var(--gacov-success) !important;
    background: var(--gacov-success) !important;
    color: #fff !important;
}
</style>
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const checkBtns = Array.from(document.querySelectorAll('.btn-check-item'));
    const progressBar = document.getElementById('loading-progress-bar');
    const progressText = document.getElementById('loading-progress-text');
    const total = checkBtns.length;
    let checkedCount = 0;

    function updateProgress() {
        const pct = total > 0 ? Math.round((checkedCount / total) * 100) : 0;
        if (progressBar) progressBar.style.width = pct + '%';
        if (progressText) progressText.textContent = checkedCount + ' / ' + total + ' productos marcados';
    }

    checkBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.loading-item-row');
            const isChecked = btn.classList.toggle('is-checked');

            btn.textContent = isChecked ? '✓' : '○';
            if (row) row.classList.toggle('is-checked', isChecked);

            checkedCount = checkBtns.filter((b) => b.classList.contains('is-checked')).length;
            updateProgress();
        });
    });

    updateProgress();
});
</script>
@endpush
