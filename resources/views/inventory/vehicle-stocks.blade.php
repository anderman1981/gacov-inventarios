@extends('layouts.app')

@section('title', 'Inventario por vehículo')

@section('content')
<div class="page-header">
    <h1 class="page-title">Inventario por vehículo</h1>
    <p class="page-subtitle">Consulta el stock asignado a cada ruta y vehículo activo.</p>
</div>

<div style="display:grid;gap:var(--space-6);">
    @forelse($routes as $route)
        <section class="panel">
            <div class="panel-header" style="display:flex;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap;">
                <span class="panel-title">{{ $route->code }} — {{ $route->name }}</span>
                <span class="badge {{ $route->vehicle_stocks->isNotEmpty() ? 'badge-success' : 'badge-neutral' }}">
                    {{ $route->driver->name ?? 'Sin conductor asignado' }}
                </span>
            </div>

            <div class="panel-body">
                <p style="color:var(--gacov-text-secondary);margin-bottom:var(--space-4);">
                    Bodega vehículo:
                    <strong>{{ $route->vehicle_warehouse?->name ?? 'No configurada' }}</strong>
                </p>

                @if($route->vehicle_stocks->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th>Categoría</th>
                                <th>Disponible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($route->vehicle_stocks as $stock)
                                <tr>
                                    <td>{{ $stock->product->name ?? '—' }}</td>
                                    <td>{{ $stock->product->sku ?? '—' }}</td>
                                    <td>{{ $stock->product->category ?? '—' }}</td>
                                    <td>{{ number_format((float) $stock->quantity, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color:var(--gacov-text-muted);">No hay stock cargado para este vehículo.</p>
                @endif
            </div>
        </section>
    @empty
        <section class="panel">
            <div class="panel-body" style="text-align:center;color:var(--gacov-text-muted);">
                No hay rutas activas configuradas.
            </div>
        </section>
    @endforelse
</div>
@endsection
