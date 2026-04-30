@extends('layouts.app')
@section('title', 'Detalle Entrega de Efectivo')

@push('styles')
<style>
.cash-shell {
    color: #0f172a;
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
}

.cash-shell .cash-detail-hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
    padding: 18px 20px;
    border: 1px solid rgba(148,163,184,.28);
    border-radius: 24px;
    background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.94));
    box-shadow: 0 12px 30px rgba(15,23,42,.06);
}

.cash-shell .cash-detail-title {
    margin: 0;
    color: #0f172a;
    letter-spacing: -0.04em;
    font-weight: 900;
}

.cash-shell .cash-detail-subtitle {
    margin-top: 6px;
    color: #475569;
    font-size: 13px;
    font-weight: 500;
}

.cash-shell .panel {
    border: 1px solid rgba(148,163,184,.26);
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 12px 28px rgba(15,23,42,.05);
}

.cash-shell .panel-header {
    background: #eef2f7;
    border-bottom: 1px solid #e2e8f0;
}

.cash-shell .panel-title {
    color: #0f172a;
    font-weight: 800;
}

.cash-shell .panel-body {
    color: #0f172a;
}

.cash-shell .cash-detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr);
    gap: 18px;
}

.cash-shell .cash-summary-card,
.cash-shell .cash-denominations-card {
    border-radius: 22px;
    border: 1px solid rgba(148,163,184,.26);
    background: #fff;
    box-shadow: 0 12px 28px rgba(15,23,42,.05);
    overflow: hidden;
}

.cash-shell .cash-summary-card .panel-header,
.cash-shell .cash-denominations-card .panel-header {
    background: #eef2f7;
}

.cash-shell .cash-info-list {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 12px 18px;
}

.cash-shell .cash-info-list dt {
    color: #475569;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 800;
}

.cash-shell .cash-info-list dd {
    margin: 0;
    color: #0f172a;
    font-weight: 700;
}

.cash-shell .cash-amount-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
}

.cash-shell .cash-amount-box {
    border-radius: 18px;
    padding: 16px;
    text-align: center;
    background: #f8fafc;
    border: 1px solid rgba(148,163,184,.24);
}

.cash-shell .cash-amount-box span {
    display: block;
    margin-bottom: 8px;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.cash-shell .cash-amount-box strong {
    font-size: 22px;
    letter-spacing: -0.03em;
}

.cash-shell .cash-pill {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(14,165,233,.10);
    color: #0369a1;
    font-size: 12px;
    font-weight: 700;
}

.cash-shell .cash-denom-group {
    margin-bottom: 18px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e2e8f0;
}

.cash-shell .cash-denom-group:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: 0;
}

.cash-shell .cash-denom-title {
    margin-bottom: 12px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.cash-shell .cash-denom-list {
    display: grid;
    gap: 8px;
}

.cash-shell .cash-denom-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 72px 96px;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
}

.cash-shell .cash-denom-row:last-child {
    border-bottom: 0;
}

.cash-shell .cash-denom-row .qty {
    text-align: center;
    color: #334155;
    font-weight: 700;
}

.cash-shell .cash-denom-row .amount {
    text-align: right;
    font-weight: 800;
}

.cash-shell .cash-denom-row.bill .amount { color: #16a34a; }
.cash-shell .cash-denom-row.coin .amount { color: #ea580c; }

.cash-shell .cash-empty {
    color: #475569;
}

@media (max-width: 1024px) {
    .cash-shell .cash-detail-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .cash-shell .cash-detail-hero {
        flex-direction: column;
    }

    .cash-shell .cash-amount-grid {
        grid-template-columns: 1fr;
    }

    .cash-shell .cash-denom-row {
        grid-template-columns: minmax(0, 1fr) 56px 82px;
    }
}
</style>
@endpush

@section('content')
<div class="cash-shell">
@include('inventory.partials.section-nav')
<div class="cash-detail-hero">
    <a href="{{ route('cash.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">
        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
    </a>
    <div>
        <h1 class="cash-detail-title">Entrega de Efectivo</h1>
        <p class="cash-detail-subtitle">{{ $cashDelivery->delivery_date->format('d/m/Y') }} · {{ $cashDelivery->driver?->name }}</p>
    </div>
</div>

<div class="cash-detail-grid">

    {{-- Info general --}}
    <div class="cash-summary-card">
        <div class="panel-header"><h3 class="panel-title">Información general</h3></div>
        <div class="panel-body">
            <dl class="cash-info-list">
                <dt>Conductor</dt>
                <dd>{{ $cashDelivery->driver?->name ?? '—' }}</dd>

                <dt>Ruta</dt>
                <dd>
                    @if($cashDelivery->route)
                    <span class="cash-pill">{{ $cashDelivery->route->name }}</span>
                    @else
                    <span style="color:var(--gacov-text-muted)">—</span>
                    @endif
                </dd>

                <dt>Fecha</dt>
                <dd>{{ $cashDelivery->delivery_date->format('d/m/Y') }}</dd>

                <dt>Entregó</dt>
                <dd>{{ $cashDelivery->deliveredBy?->name ?? '—' }}</dd>

                @if($cashDelivery->notes)
                <dt>Notas</dt>
                <dd style="font-size:13px;font-weight:500;color:#334155">{{ $cashDelivery->notes }}</dd>
                @endif
            </dl>

            <div class="cash-amount-grid">
                <div class="cash-amount-box" style="background:rgba(16,185,129,.08)">
                    <span>Billetes</span>
                    <strong style="color:#16a34a">${{ number_format($cashDelivery->total_bills, 0, ',', '.') }}</strong>
                </div>
                <div class="cash-amount-box" style="background:rgba(245,158,11,.08)">
                    <span>Monedas</span>
                    <strong style="color:#ea580c">${{ number_format($cashDelivery->total_coins, 0, ',', '.') }}</strong>
                </div>
                <div class="cash-amount-box" style="background:rgba(14,165,233,.08)">
                    <span>Total</span>
                    <strong style="color:#0ea5e9">${{ number_format($cashDelivery->total_amount, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Desglose denominaciones --}}
    <div class="cash-denominations-card">
        <div class="panel-header"><h3 class="panel-title">Desglose por denominación</h3></div>
        <div class="panel-body">

            @php
                $billLabels = ['bill_100000'=>'$100.000','bill_50000'=>'$50.000','bill_20000'=>'$20.000','bill_10000'=>'$10.000','bill_5000'=>'$5.000','bill_2000'=>'$2.000','bill_1000'=>'$1.000'];
                $coinLabels = ['coin_1000'=>'$1.000','coin_500'=>'$500','coin_200'=>'$200','coin_100'=>'$100','coin_50'=>'$50'];
                $hasBills = collect(array_keys($billLabels))->some(fn ($field) => $cashDelivery->$field > 0);
                $hasCoins = collect(array_keys($coinLabels))->some(fn ($field) => $cashDelivery->$field > 0);
            @endphp

            <div class="cash-denom-group">
            <div class="cash-denom-title" style="color:#16a34a">Billetes</div>
                @foreach($billLabels as $field => $label)
                @if($cashDelivery->$field > 0)
                <div class="cash-denom-row bill">
                    <div>{{ $label }}</div>
                    <div class="qty">× {{ $cashDelivery->$field }}</div>
                    <div class="amount">${{ number_format($cashDelivery->$field * \App\Models\DriverCashDelivery::BILL_DENOMINATIONS[$field], 0, ',', '.') }}</div>
                </div>
                @endif
                @endforeach
                @if(! $hasBills)
                <div class="cash-empty">Sin billetes registrados.</div>
                @endif
            </div>

            <div class="cash-denom-group">
            <div class="cash-denom-title" style="color:#ea580c">Monedas</div>
                @foreach($coinLabels as $field => $label)
                @if($cashDelivery->$field > 0)
                <div class="cash-denom-row coin">
                    <div>{{ $label }}</div>
                    <div class="qty">× {{ $cashDelivery->$field }}</div>
                    <div class="amount">${{ number_format($cashDelivery->$field * \App\Models\DriverCashDelivery::COIN_DENOMINATIONS[$field], 0, ',', '.') }}</div>
                </div>
                @endif
                @endforeach
                @if(! $hasCoins)
                <div class="cash-empty">Sin monedas registradas.</div>
                @endif
            </div>

        </div>
    </div>

</div>
</div>
@endsection
