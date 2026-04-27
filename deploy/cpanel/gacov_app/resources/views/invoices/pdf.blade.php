<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Factura {{ $invoice->full_number }}</title>
    <style>
        @page {
            margin: 16mm 14mm 16mm 14mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.55;
            color: #111827;
            background: #ffffff;
        }

        .page {
            width: 100%;
        }

        .document {
            width: 100%;
            border: 1px solid #d1d5db;
            padding: 18px;
        }

        .topband {
            display: table;
            width: 100%;
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .brand {
            display: table-cell;
            width: 58%;
            vertical-align: top;
        }

        .brand-row {
            display: table;
            width: 100%;
        }

        .brand-mark {
            display: table-cell;
            width: 56px;
            vertical-align: top;
        }

        .brand-badge {
            width: 48px;
            height: 48px;
            border: 1px solid #111827;
            background: #111827;
            color: #ffffff;
            text-align: center;
            line-height: 46px;
            font-size: 14px;
            font-weight: 700;
        }

        .brand-copy {
            display: table-cell;
            vertical-align: top;
            padding-left: 12px;
        }

        .brand-name {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
        }

        .brand-subtitle {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 8px;
        }

        .brand-details {
            font-size: 10px;
            color: #374151;
        }

        .docbox {
            display: table-cell;
            width: 42%;
            vertical-align: top;
            text-align: right;
        }

        .doc-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #111827;
            margin-bottom: 4px;
        }

        .doc-number {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .badges {
            margin-bottom: 8px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            margin-left: 6px;
            border: 1px solid #d1d5db;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .badge-neutral { background: #f3f4f6; color: #374151; }
        .badge-primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .badge-success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .badge-warning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .badge-danger { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

        .doc-meta {
            font-size: 10px;
            color: #374151;
            line-height: 1.6;
        }

        .summary-band {
            width: 100%;
            margin-bottom: 14px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .summary-band td {
            padding: 10px 12px;
            vertical-align: top;
            border-right: 1px solid #e5e7eb;
        }

        .summary-band td:last-child {
            border-right: 0;
        }

        .summary-label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        .summary-value.success { color: #047857; }
        .summary-value.warning { color: #b45309; }
        .summary-value.danger { color: #b91c1c; }

        .section {
            margin-bottom: 14px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #111827;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .parties {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 0;
        }

        .party-card {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            border: 1px solid #d1d5db;
            padding: 12px;
        }

        .party-card + .party-card {
            border-left: 0;
        }

        .party-heading {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .party-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
        }

        .party-line {
            font-size: 10px;
            color: #374151;
            margin-bottom: 3px;
        }

        .notice {
            border: 1px solid #bfdbfe;
            border-left: 4px solid #3b82f6;
            background: #eff6ff;
            padding: 12px 14px;
        }

        .notice-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #1d4ed8;
            margin-bottom: 5px;
        }

        .notice-text {
            font-size: 10px;
            color: #1f2937;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 7px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .items-table th {
            background: #111827;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .05em;
            text-align: left;
        }

        .items-table .right,
        .items-table th.right {
            text-align: right;
        }

        .items-table .center,
        .items-table th.center {
            text-align: center;
        }

        .item-desc {
            font-size: 11px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 3px;
        }

        .item-meta {
            font-size: 9px;
            color: #6b7280;
            margin-top: 2px;
        }

        .totals-wrap {
            width: 100%;
            margin-top: 10px;
            display: table;
        }

        .totals-note {
            display: table-cell;
            width: 54%;
            vertical-align: top;
            padding-right: 10px;
        }

        .totals-box {
            display: table-cell;
            width: 46%;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d1d5db;
        }

        .totals-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        .totals-table td:last-child {
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
        }

        .totals-table .label {
            color: #374151;
        }

        .totals-table .total-row td {
            background: #111827;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            border-bottom: 0;
        }

        .totals-table .total-row td:last-child {
            color: #67e8f9;
        }

        .totals-table .paid-row td {
            background: #ecfdf5;
            color: #047857;
        }

        .totals-table .balance-row td {
            background: #fff7ed;
            color: #b45309;
        }

        .small-box {
            border: 1px solid #e5e7eb;
            background: #fafafa;
            padding: 12px;
        }

        .small-box p {
            margin-bottom: 6px;
            font-size: 10px;
            color: #374151;
        }

        .small-box p:last-child {
            margin-bottom: 0;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .payments-table th,
        .payments-table td {
            border: 1px solid #e5e7eb;
            padding: 7px;
            font-size: 10px;
        }

        .payments-table th {
            background: #f3f4f6;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-size: 9px;
            color: #374151;
            text-align: left;
        }

        .payments-table .right {
            text-align: right;
        }

        .dian-box {
            border: 1px solid #dbeafe;
            background: #f8fbff;
            padding: 12px;
        }

        .dian-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .dian-item {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
            padding-right: 10px;
        }

        .dian-label {
            display: block;
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 3px;
        }

        .dian-value {
            font-size: 10px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .footer {
            margin-top: 14px;
            border-top: 1px solid #d1d5db;
            padding-top: 10px;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            line-height: 1.5;
        }

        .footer strong {
            color: #111827;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .document {
                border: 0;
                padding: 0;
            }

            .section,
            .notice,
            .dian-box,
            .small-box {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @php
        $statusTone = match ($invoice->status) {
            'paid' => 'success',
            'issued' => 'primary',
            'cancelled' => 'danger',
            'expired' => 'warning',
            default => 'neutral',
        };

        $paymentTone = match ($invoice->payment_status) {
            'paid' => 'success',
            'partial' => 'warning',
            'overdue' => 'danger',
            default => 'neutral',
        };
    @endphp

    <div class="page">
        <div class="document">
            <div class="topband">
                <div class="brand">
                    <div class="brand-row">
                        <div class="brand-mark">
                            <div class="brand-badge">AMR</div>
                        </div>
                        <div class="brand-copy">
                            <div class="brand-name">{{ $invoice->issuer_name }}</div>
                            <div class="brand-subtitle">Factura de venta y cobro programado</div>
                            <div class="brand-details">
                                NIT: {{ $invoice->issuer_nit }}<br>
                                @if($invoice->issuer_address){{ $invoice->issuer_address }}<br>@endif
                                @if($invoice->issuer_phone)Tel: {{ $invoice->issuer_phone }}<br>@endif
                                @if($invoice->issuer_email){{ $invoice->issuer_email }}@endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="docbox">
                    <div class="doc-title">Factura</div>
                    <div class="doc-number">{{ $invoice->full_number }}</div>
                    <div class="badges">
                        <span class="badge badge-{{ $statusTone }}">{{ $invoice->status_label }}</span>
                        <span class="badge badge-{{ $paymentTone }}">{{ $invoice->payment_status_label }}</span>
                    </div>
                    <div class="doc-meta">
                        Fecha de emisión: {{ $invoice->issue_date->format('d/m/Y') }}<br>
                        Fecha de vencimiento: {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'Sin vencimiento' }}
                        @if($invoice->paid_at)<br>Fecha de pago: {{ $invoice->paid_at->format('d/m/Y') }}@endif
                    </div>
                </div>
            </div>

            <table class="summary-band">
                <tr>
                    <td>
                        <span class="summary-label">Total facturado</span>
                        <span class="summary-value">${{ number_format((float) $invoice->total, 2) }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Pagado</span>
                        <span class="summary-value success">${{ number_format((float) $invoice->paid_amount, 2) }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Saldo pendiente</span>
                        <span class="summary-value {{ $invoice->balance_due > 0 ? 'warning' : 'success' }}">${{ number_format((float) $invoice->balance_due, 2) }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Término de pago</span>
                        <span class="summary-value">{{ $invoice->due_date ? 'Según vencimiento' : 'Contado / programado' }}</span>
                    </td>
                </tr>
            </table>

            <div class="section">
                <div class="section-title">Partes</div>
                <div class="parties">
                    <div class="party-card">
                        <div class="party-heading">Emisor</div>
                        <div class="party-name">{{ $invoice->issuer_name }}</div>
                        <div class="party-line"><strong>NIT:</strong> {{ $invoice->issuer_nit }}</div>
                        @if($invoice->issuer_address)<div class="party-line"><strong>Dirección:</strong> {{ $invoice->issuer_address }}</div>@endif
                        @if($invoice->issuer_phone)<div class="party-line"><strong>Tel:</strong> {{ $invoice->issuer_phone }}</div>@endif
                        @if($invoice->issuer_email)<div class="party-line"><strong>Correo:</strong> {{ $invoice->issuer_email }}</div>@endif
                    </div>
                    <div class="party-card">
                        <div class="party-heading">Cliente</div>
                        <div class="party-name">{{ $invoice->client_name }}</div>
                        <div class="party-line"><strong>NIT:</strong> {{ $invoice->client_nit }}</div>
                        @if($invoice->client_address)<div class="party-line"><strong>Dirección:</strong> {{ $invoice->client_address }}</div>@endif
                        @if($invoice->client_phone)<div class="party-line"><strong>Tel:</strong> {{ $invoice->client_phone }}</div>@endif
                        @if($invoice->client_email)<div class="party-line"><strong>Correo:</strong> {{ $invoice->client_email }}</div>@endif
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Detalle de cobro</div>
                <div class="notice">
                    <div class="notice-title">Ciclo facturado</div>
                    <div class="notice-text">
                        Este documento consolida el valor generado para el periodo o hito acordado.
                        El pago registrado se descuenta del total y el saldo pendiente queda visible para
                        mantener trazabilidad de los abonos realizados dentro de los tiempos establecidos.
                    </div>
                </div>
            </div>

            <div class="section">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 34%;">Descripción</th>
                            <th class="center" style="width: 8%;">Und</th>
                            <th class="right" style="width: 10%;">Cant.</th>
                            <th class="right" style="width: 12%;">P. Unit.</th>
                            <th class="right" style="width: 8%;">Desc.</th>
                            <th class="right" style="width: 10%;">Subtotal</th>
                            <th class="right" style="width: 8%;">IVA</th>
                            <th class="right" style="width: 10%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                        <tr>
                            <td>
                                <div class="item-desc">{{ $item->description }}</div>
                                @if($item->product_key)
                                <div class="item-meta">Código: {{ $item->product_key }}</div>
                                @endif
                                @if($item->plan_name)
                                <div class="item-meta">Plan: {{ $item->plan_name }}</div>
                                @endif
                                @if($item->billing_period)
                                <div class="item-meta">Periodo: {{ $item->billing_period }}</div>
                                @endif
                                @if($item->service_start || $item->service_end)
                                <div class="item-meta">
                                    Servicio:
                                    {{ $item->service_start?->format('d/m/Y') ?? 'N/A' }}
                                    -
                                    {{ $item->service_end?->format('d/m/Y') ?? 'N/A' }}
                                </div>
                                @endif
                            </td>
                            <td class="center">{{ $item->unit }}</td>
                            <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="right">${{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="right">{{ (float) $item->discount_rate > 0 ? number_format((float) $item->discount_rate, 2).'%' : '-' }}</td>
                            <td class="right">${{ number_format((float) $item->subtotal, 2) }}</td>
                            <td class="right">{{ number_format((float) $item->tax_rate, 2) }}%</td>
                            <td class="right"><strong>${{ number_format((float) $item->total, 2) }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="center">No hay ítems registrados para esta factura.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="totals-wrap">
                <div class="totals-note">
                    <div class="small-box">
                        <p><strong>Observación comercial:</strong> La factura refleja el cobro del ciclo vigente y los abonos ya registrados, si los hubiera.</p>
                        <p><strong>Estado del cobro:</strong> {{ $invoice->payment_status_label }}.</p>
                        <p><strong>Estado del documento:</strong> {{ $invoice->status_label }}.</p>
                        @if($invoice->terms)
                        <p><strong>Términos:</strong> {{ $invoice->terms }}</p>
                        @endif
                    </div>
                </div>
                <div class="totals-box">
                    <table class="totals-table">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td>${{ number_format((float) $invoice->subtotal, 2) }}</td>
                        </tr>
                        @if((float) $invoice->discount_amount > 0)
                        <tr>
                            <td class="label">Descuento</td>
                            <td>-${{ number_format((float) $invoice->discount_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="label">IVA ({{ number_format((float) $invoice->tax_rate, 2) }}%)</td>
                            <td>${{ number_format((float) $invoice->tax_amount, 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td>TOTAL</td>
                            <td>${{ number_format((float) $invoice->total, 2) }}</td>
                        </tr>
                        @if((float) $invoice->paid_amount > 0)
                        <tr class="paid-row">
                            <td>Pagado</td>
                            <td>-${{ number_format((float) $invoice->paid_amount, 2) }}</td>
                        </tr>
                        <tr class="balance-row">
                            <td>Saldo pendiente</td>
                            <td>${{ number_format((float) $invoice->balance_due, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($invoice->payments->count() > 0)
            <div class="section">
                <div class="section-title">Pagos registrados</div>
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th style="width: 16%;">Fecha</th>
                            <th style="width: 20%;">Método</th>
                            <th>Referencia</th>
                            <th class="right" style="width: 16%;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td>{{ $payment->method_label }}</td>
                            <td>{{ $payment->reference ?? '-' }}</td>
                            <td class="right">${{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($invoice->dian_sequential_code || $invoice->dian_resolution_number || $invoice->dian_from_date)
            <div class="section">
                <div class="section-title">Información DIAN</div>
                <div class="dian-box">
                    <div class="dian-grid">
                        @if($invoice->dian_sequential_code)
                        <div class="dian-item">
                            <span class="dian-label">Código de autorización</span>
                            <div class="dian-value">{{ $invoice->dian_sequential_code }}</div>
                        </div>
                        @endif
                        @if($invoice->dian_resolution_number)
                        <div class="dian-item">
                            <span class="dian-label">Número de resolución</span>
                            <div class="dian-value">{{ $invoice->dian_resolution_number }}</div>
                        </div>
                        @endif
                        @if($invoice->dian_from_date)
                        <div class="dian-item">
                            <span class="dian-label">Vigencia</span>
                            <div class="dian-value">
                                {{ $invoice->dian_from_date->format('d/m/Y') }} - {{ $invoice->dian_to_date ? $invoice->dian_to_date->format('d/m/Y') : 'N/A' }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @if($invoice->notes || $invoice->terms)
            <div class="section">
                <div class="section-title">Notas y condiciones</div>
                <div class="small-box">
                    @if($invoice->notes)
                    <p><strong>Notas:</strong> {{ $invoice->notes }}</p>
                    @endif
                    @if($invoice->terms)
                    <p><strong>Términos y condiciones:</strong> {{ $invoice->terms }}</p>
                    @endif
                </div>
            </div>
            @endif

            <div class="footer">
                <p><strong>{{ $invoice->issuer_name }}</strong> - NIT {{ $invoice->issuer_nit }}</p>
                <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
                <p>AMR Tech - GACOV Inventarios</p>
            </div>
        </div>
    </div>
</body>
</html>
