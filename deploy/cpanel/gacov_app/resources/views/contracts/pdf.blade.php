<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrato {{ $contract->contract_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Georgia, "Times New Roman", serif;
            color: #111;
            font-size: 11.5pt;
            line-height: 1.75;
            background: #fff;
            margin: 0;
        }
        .page {
            width: 100%;
            padding: 10px;
            display: flex;
            justify-content: center;
        }
        .contract-paper {
            border: 1px solid #d1d5db;
            border-radius: 18px;
            padding: 5px;
            width: 100%;
            max-width: 750px;
        }
        .contract-header {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }
        .contract-header > div:first-child {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .contract-header > .contract-status {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
            font-weight: 700;
            font-size: 9px;
            line-height: 1.2;
            white-space: normal;
        }
        .contract-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #b91c1c;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        h1 {
            font-size: 22px;
            margin: 10px 0 6px;
        }
        .contract-subtitle {
            color: #6b7280;
            font-size: 11px;
        }
        h2 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin: 0 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        p { margin: 0 0 10px; text-align: justify; }
        .contract-section { margin-top: 14px; margin-bottom: 10px; }
        .contract-alert,
        .parties-grid,
        .signatures-grid,
        .contract-closing {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .contract-alert {
            margin: 18px 0;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid #dbe3ee;
            background: #f8fafc;
        }
        .contract-alert h3 {
            margin: 0 0 6px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .contract-alert.info {
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .contract-alert.warning {
            background: #fffbeb;
            border-color: #fcd34d;
        }
        .contract-alert.success {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }
        .highlight-row td {
            background: #f0fdf4 !important;
            font-weight: 700;
        }
        .sla-table th {
            background: #374151;
            color: #fff;
        }
        .parties-grid,
        .contract-columns,
        .signatures-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 0 10px;
        }
        .party-card,
        .contract-columns > div,
        .signature-card {
            display: table-cell;
            vertical-align: top;
            width: 49%;
        }
        .party-card,
        .contract-columns > div,
        .signature-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px;
            background: #fafafa;
        }
        .party-card h3 { margin: 0 0 8px; font-size: 12px; text-transform: uppercase; }
        .party-card p,
        .signature-card p { margin: 0 0 4px; font-size: 11px; }
        .contract-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            table-layout: fixed;
        }
        .contract-table thead {
            display: table-header-group;
        }
        .contract-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            font-size: 11px;
            vertical-align: top;
            word-break: break-word;
        }
        .contract-table td:first-child {
            width: 42%;
            background: #f9fafb;
            font-weight: 700;
        }
        .contract-table.table-wide td:first-child {
            width: auto;
        }
        .contract-table.table-wide td {
            width: auto;
        }
        .contract-table.table-wide td,
        .contract-table.table-wide th {
            font-size: 10px;
            line-height: 1.45;
        }
        .contract-table thead td {
            background: #111827;
            color: #fff;
            font-weight: 700;
        }
        ul { margin: 0; padding-left: 18px; }
        li { margin-bottom: 6px; }
        .signature-line {
            height: 76px;
            border-bottom: 1px solid #111827;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .signature-line img {
            max-height: 68px;
            max-width: 100%;
        }
        .provider-line {
            font-weight: 700;
            font-size: 16px;
            color: #b91c1c;
        }
        .signature-name { font-weight: 700; }
        .signature-meta { color: #6b7280; }
        .contract-footer {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
        }
        .contract-note { color: #374151; font-style: italic; }
        .contract-closing { margin-top: 18px; }

        @media print {
            @page {
                margin: 10px;
            }

            .page {
                padding: 0;
                display: block;
            }

            .contract-header > div:first-child {
                width: 100%;
            }

            .contract-status {
                display: none;
            }

            .contract-closing {
                page-break-before: always;
                break-before: page;
                margin-top: 0;
            }

            .contract-paper {
                margin: 0 auto;
                width: 100%;
                max-width: 100%;
            }

            .contract-footer {
                margin-top: 4px;
                padding-top: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        @php($isPdf = true)
        @include('contracts.partials.document')
    </div>
</body>
</html>
