@extends('layouts.app')
@section('title', 'Detalle Entrega de Efectivo')

@section('content')
<div class="page-header" style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-6)">
    <a href="{{ route('cash.index') }}" style="color:var(--gacov-text-muted);text-decoration:none">
        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
    </a>
    <div>
        <h1 class="page-title">Entrega de Efectivo</h1>
        <p class="page-subtitle">{{ $cashDelivery->delivery_date->format('d \d\e F \d\e Y') }} · {{ $cashDelivery->driver?->name }}</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">

    {{-- Info general --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title">Información general</h3></div>
        <div class="panel-body">
            <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-6)">
                <dt style="color:var(--gacov-text-muted);font-size:13px">Conductor</dt>
                <dd style="font-weight:600">{{ $cashDelivery->driver?->name ?? '—' }}</dd>

                <dt style="color:var(--gacov-text-muted);font-size:13px">Ruta</dt>
                <dd>
                    @if($cashDelivery->route)
                    <span class="badge badge-info">{{ $cashDelivery->route->name }}</span>
                    @else
                    <span style="color:var(--gacov-text-muted)">—</span>
                    @endif
                </dd>

                <dt style="color:var(--gacov-text-muted);font-size:13px">Fecha</dt>
                <dd>{{ $cashDelivery->delivery_date->format('d/m/Y') }}</dd>

                <dt style="color:var(--gacov-text-muted);font-size:13px">Entregó</dt>
                <dd style="font-size:13px">{{ $cashDelivery->deliveredBy?->name ?? '—' }}</dd>

                @if($cashDelivery->notes)
                <dt style="color:var(--gacov-text-muted);font-size:13px">Notas</dt>
                <dd style="font-size:13px">{{ $cashDelivery->notes }}</dd>
                @endif
            </dl>

            <div style="margin-top:var(--space-6);display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-4)">
                <div style="text-align:center;padding:var(--space-4);background:rgba(16,185,129,.08);border-radius:var(--radius-md)">
                    <p style="font-size:11px;color:var(--gacov-text-muted);margin-bottom:4px">BILLETES</p>
                    <p style="font-size:18px;font-weight:700;color:var(--gacov-success)">${{ number_format($cashDelivery->total_bills, 0, ',', '.') }}</p>
                </div>
                <div style="text-align:center;padding:var(--space-4);background:rgba(245,158,11,.08);border-radius:var(--radius-md)">
                    <p style="font-size:11px;color:var(--gacov-text-muted);margin-bottom:4px">MONEDAS</p>
                    <p style="font-size:18px;font-weight:700;color:var(--gacov-warning)">${{ number_format($cashDelivery->total_coins, 0, ',', '.') }}</p>
                </div>
                <div style="text-align:center;padding:var(--space-4);background:rgba(0,212,255,.08);border-radius:var(--radius-md)">
                    <p style="font-size:11px;color:var(--gacov-text-muted);margin-bottom:4px">TOTAL</p>
                    <p style="font-size:18px;font-weight:700;color:var(--gacov-primary)">${{ number_format($cashDelivery->total_amount, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Desglose denominaciones --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title">Desglose por denominación</h3></div>
        <div class="panel-body">

            <p style="font-size:12px;font-weight:600;color:var(--gacov-success);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Billetes</p>
            @php
                $billLabels = ['bill_100000'=>'$100.000','bill_50000'=>'$50.000','bill_20000'=>'$20.000','bill_10000'=>'$10.000','bill_5000'=>'$5.000','bill_2000'=>'$2.000','bill_1000'=>'$1.000'];
                $coinLabels = ['coin_1000'=>'$1.000','coin_500'=>'$500','coin_200'=>'$200','coin_100'=>'$100','coin_50'=>'$50'];
            @endphp

            <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:var(--space-5)">
                @foreach($billLabels as $field => $label)
                @if($cashDelivery->$field > 0)
                <tr style="border-bottom:1px solid var(--gacov-border)">
                    <td style="padding:6px 0;color:var(--gacov-text-secondary)">{{ $label }}</td>
                    <td style="text-align:center;padding:6px 8px;color:var(--gacov-text-muted)">× {{ $cashDelivery->$field }}</td>
                    <td style="text-align:right;padding:6px 0;font-weight:600;color:var(--gacov-success)">
                        ${{ number_format($cashDelivery->$field * \App\Models\DriverCashDelivery::BILL_DENOMINATIONS[$field], 0, ',', '.') }}
                    </td>
                </tr>
                @endif
                @endforeach
            </table>

            <p style="font-size:12px;font-weight:600;color:var(--gacov-warning);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Monedas</p>
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                @foreach($coinLabels as $field => $label)
                @if($cashDelivery->$field > 0)
                <tr style="border-bottom:1px solid var(--gacov-border)">
                    <td style="padding:6px 0;color:var(--gacov-text-secondary)">{{ $label }}</td>
                    <td style="text-align:center;padding:6px 8px;color:var(--gacov-text-muted)">× {{ $cashDelivery->$field }}</td>
                    <td style="text-align:right;padding:6px 0;font-weight:600;color:var(--gacov-warning)">
                        ${{ number_format($cashDelivery->$field * \App\Models\DriverCashDelivery::COIN_DENOMINATIONS[$field], 0, ',', '.') }}
                    </td>
                </tr>
                @endif
                @endforeach
            </table>

            @php
                $hasBills = collect(array_keys(\App\Models\DriverCashDelivery::BILL_DENOMINATIONS))->some(fn($f) => $cashDelivery->$f > 0);
                $hasCoins = collect(array_keys(\App\Models\DriverCashDelivery::COIN_DENOMINATIONS))->some(fn($f) => $cashDelivery->$f > 0);
            @endphp
            @if(!$hasBills && !$hasCoins)
            <p style="color:var(--gacov-text-muted);font-size:13px;text-align:center;padding:var(--space-4) 0">Sin denominaciones registradas.</p>
            @endif

        </div>
    </div>

</div>
@endsection
