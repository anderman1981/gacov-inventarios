<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Factura {{ $invoice->full_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .company-info {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .company-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #00D4FF, #7C3AED);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
        }
        
        .company-details {
            color: #666;
            font-size: 10px;
            margin-top: 5px;
        }
        
        .invoice-title {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        
        .invoice-title h1 {
            font-size: 24px;
            color: #00D4FF;
            margin-bottom: 5px;
        }
        
        .invoice-number {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 15px;
        }
        
        /* Info Table */
        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .info-table td {
            padding: 8px;
            vertical-align: top;
        }
        
        .info-table .label {
            font-weight: bold;
            color: #666;
            width: 40%;
        }
        
        /* Addresses */
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .address-box {
            display: table-cell;
            width: 48%;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        .address-box.issuer {
            margin-right: 2%;
        }
        
        .address-box.client {
            margin-left: 2%;
        }
        
        .address-box h3 {
            font-size: 11px;
            color: #00D4FF;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .address-box .name {
            font-weight: bold;
            font-size: 14px;
            color: #1a1a2e;
            margin-bottom: 5px;
        }
        
        .address-box .detail {
            font-size: 10px;
            color: #666;
            margin-bottom: 3px;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th {
            background: #1a1a2e;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .items-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
        }
        
        .items-table th:nth-child(n+3) {
            text-align: right;
        }
        
        .items-table td:nth-child(n+3) {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        
        .items-table .item-desc {
            font-weight: 500;
        }
        
        .items-table .item-meta {
            font-size: 9px;
            color: #888;
            margin-top: 3px;
        }
        
        /* Totals */
        .totals-section {
            width: 300px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        
        .totals-table {
            width: 100%;
        }
        
        .totals-table td {
            padding: 8px 10px;
        }
        
        .totals-table td:first-child {
            color: #666;
        }
        
        .totals-table td:last-child {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        
        .totals-table .subtotal-row {
            border-bottom: 1px solid #e0e0e0;
        }
        
        .totals-table .total-row {
            background: #1a1a2e;
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        
        .totals-table .total-row td {
            padding: 15px 10px;
        }
        
        .totals-table .total-row td:last-child {
            color: #00D4FF;
        }
        
        /* DIAN Section */
        .dian-section {
            background: #f0f8ff;
            border: 1px solid #00D4FF;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .dian-section h3 {
            font-size: 12px;
            color: #00D4FF;
            margin-bottom: 10px;
        }
        
        .dian-grid {
            display: table;
            width: 100%;
        }
        
        .dian-item {
            display: table-cell;
            width: 33.33%;
        }
        
        .dian-label {
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
        }
        
        .dian-value {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a2e;
        }
        
        /* Notes */
        .notes-section {
            margin-bottom: 30px;
        }
        
        .notes-section h4 {
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .notes-section p {
            font-size: 10px;
            color: #444;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            color: #888;
            font-size: 9px;
        }
        
        .footer p {
            margin-bottom: 3px;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-draft {
            background: #e0e0e0;
            color: #666;
        }
        
        .status-issued {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-paid {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .status-cancelled {
            background: #ffebee;
            color: #d32f2f;
        }
        
        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        {{-- HEADER --}}
        <div class="header">
            <div class="company-info">
                <div class="company-logo">G</div>
                <div class="company-name">{{ $invoice->issuer_name }}</div>
                <div class="company-details">
                    @if($invoice->issuer_address){{ $invoice->issuer_address }}<br>@endif
                    NIT: {{ $invoice->issuer_nit }}<br>
                    @if($invoice->issuer_phone)Tel: {{ $invoice->issuer_phone }}<br>@endif
                    @if($invoice->issuer_email)Email: {{ $invoice->issuer_email }}@endif
                </div>
            </div>
            <div class="invoice-title">
                <h1>FACTURA DE VENTA</h1>
                <div class="invoice-number">{{ $invoice->full_number }}</div>
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ $invoice->status_label }}
                </span>
            </div>
        </div>

        {{-- DATES INFO --}}
        <table class="info-table">
            <tr>
                <td class="label">Fecha de Emisión:</td>
                <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                <td class="label">Fecha de Vencimiento:</td>
                <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'Sin vencimiento' }}</td>
            </tr>
            @if($invoice->paid_at)
            <tr>
                <td class="label">Fecha de Pago:</td>
                <td colspan="3" style="color: #388e3c;">{{ $invoice->paid_at->format('d/m/Y') }}</td>
            </tr>
            @endif
        </table>

        {{-- ADDRESSES --}}
        <div class="addresses">
            <div class="address-box issuer">
                <h3>EMISOR</h3>
                <div class="name">{{ $invoice->issuer_name }}</div>
                <div class="detail">NIT: {{ $invoice->issuer_nit }}</div>
                @if($invoice->issuer_address)<div class="detail">{{ $invoice->issuer_address }}</div>@endif
                @if($invoice->issuer_phone)<div class="detail">Tel: {{ $invoice->issuer_phone }}</div>@endif
                @if($invoice->issuer_email)<div class="detail">{{ $invoice->issuer_email }}</div>@endif
            </div>
            <div class="address-box client">
                <h3>CLIENTE</h3>
                <div class="name">{{ $invoice->client_name }}</div>
                <div class="detail">NIT: {{ $invoice->client_nit }}</div>
                @if($invoice->client_address)<div class="detail">{{ $invoice->client_address }}</div>@endif
                @if($invoice->client_phone)<div class="detail">Tel: {{ $invoice->client_phone }}</div>@endif
                @if($invoice->client_email)<div class="detail">{{ $invoice->client_email }}</div>@endif
            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Descripción</th>
                    <th style="width: 8%;">Und</th>
                    <th style="width: 10%;">Cantidad</th>
                    <th style="width: 15%;">Precio Unit.</th>
                    <th style="width: 8%;">Desc.</th>
                    <th style="width: 8%;">IVA</th>
                    <th style="width: 11%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-desc">{{ $item->description }}</div>
                        @if($item->product_key)
                        <div class="item-meta">Código: {{ $item->product_key }}</div>
                        @endif
                        @if($item->billing_period)
                        <div class="item-meta">Período: {{ $item->billing_period }}</div>
                        @endif
                    </td>
                    <td>{{ $item->unit }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->discount_rate > 0 ? $item->discount_rate . '%' : '-' }}</td>
                    <td>{{ $item->tax_rate }}%</td>
                    <td>${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- TOTALS --}}
        <div class="totals-section">
            <table class="totals-table">
                <tr class="subtotal-row">
                    <td>Subtotal:</td>
                    <td>${{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td>Descuento:</td>
                    <td>-${{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>IVA ({{ $invoice->tax_rate }}%):</td>
                    <td>${{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td>${{ number_format($invoice->total, 2) }}</td>
                </tr>
                @if($invoice->paid_amount > 0)
                <tr>
                    <td>Pagado:</td>
                    <td style="color: #388e3c;">-${{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr style="font-weight: bold; font-size: 14px;">
                    <td>SALDO PENDIENTE:</td>
                    <td style="color: #d32f2f;">
                        ${{ number_format($invoice->balance_due, 2) }}
                    </td>
                </tr>
                @endif
            </table>
        </div>

        {{-- DIAN INFO --}}
        @if($invoice->dian_sequential_code || $invoice->dian_resolution_number)
        <div class="dian-section">
            <h3>INFORMACIÓN DIAN</h3>
            <div class="dian-grid">
                @if($invoice->dian_sequential_code)
                <div class="dian-item">
                    <div class="dian-label">Código de Autorización</div>
                    <div class="dian-value">{{ $invoice->dian_sequential_code }}</div>
                </div>
                @endif
                @if($invoice->dian_resolution_number)
                <div class="dian-item">
                    <div class="dian-label">Número de Resolución</div>
                    <div class="dian-value">{{ $invoice->dian_resolution_number }}</div>
                </div>
                @endif
                @if($invoice->dian_from_date)
                <div class="dian-item">
                    <div class="dian-label">Vigencia</div>
                    <div class="dian-value">
                        {{ $invoice->dian_from_date->format('d/m/Y') }} - {{ $invoice->dian_to_date ? $invoice->dian_to_date->format('d/m/Y') : 'N/A' }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- NOTES --}}
        @if($invoice->notes || $invoice->terms)
        <div class="notes-section">
            @if($invoice->notes)
            <h4>Notas</h4>
            <p>{{ $invoice->notes }}</p>
            @endif
            @if($invoice->terms)
            <h4 style="margin-top: 10px;">Términos y Condiciones</h4>
            <p>{{ $invoice->terms }}</p>
            @endif
        </div>
        @endif

        {{-- FOOTER --}}
        <div class="footer">
            <p><strong>{{ $invoice->issuer_name }}</strong></p>
            <p>NIT: {{ $invoice->issuer_nit }}</p>
            <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
            <p style="margin-top: 10px;">Desarrollado por AMR Tech · GACOV Inventarios v1.0</p>
        </div>
    </div>
</body>
</html>
