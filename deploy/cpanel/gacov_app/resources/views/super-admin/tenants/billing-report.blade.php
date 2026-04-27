<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte financiero interno · {{ $tenant->name }}</title>
    <style>
        :root {
            --amr-primary: #00D4FF;
            --amr-bg-base: #0A0E1A;
            --amr-bg-surface: #111827;
            --amr-border: #1F2937;
            --amr-text-primary: #F9FAFB;
            --amr-text-secondary: #9CA3AF;
            --amr-success: #10B981;
            --amr-warning: #F59E0B;
            --font-sans: 'Inter', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', ui-monospace, monospace;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 32px;
            font-family: var(--font-sans);
            background: var(--amr-bg-base);
            color: var(--amr-text-primary);
        }
        .sheet {
            max-width: 1120px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }
        .card {
            background: var(--amr-bg-surface);
            border: 1px solid var(--amr-border);
            border-radius: 16px;
            padding: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-top: 6px;
        }
        .label {
            font-size: 11px;
            color: var(--amr-text-secondary);
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            font-size: 13px;
        }
        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--amr-border);
            text-align: left;
            vertical-align: top;
        }
        th {
            font-size: 11px;
            color: var(--amr-text-secondary);
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .mono {
            font-family: var(--font-mono);
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-success {
            background: rgba(16,185,129,.12);
            color: var(--amr-success);
        }
        .badge-warning {
            background: rgba(245,158,11,.12);
            color: var(--amr-warning);
        }
        @media print {
            body {
                background: #fff;
                color: #111827;
                padding: 0;
            }
            .card {
                border-color: #E5E7EB;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <div>
                <div class="label">Reporte interno AMR Tech</div>
                <h1 style="margin:8px 0 6px; font-size:30px;">Control financiero por fases</h1>
                <div style="font-size:15px; color:var(--amr-text-secondary);">
                    {{ $tenant->name }} · {{ $tenant->slug }} · generado {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>
            <div class="card" style="min-width:280px;">
                <div class="label">Resumen ejecutivo</div>
                <div style="margin-top:10px; display:flex; flex-direction:column; gap:8px; font-size:13px;">
                    <div>Fase actual: <strong>F{{ $summary['current_phase'] }} · {{ $summary['current_phase_label'] }}</strong></div>
                    <div>Plan técnico: <strong>{{ $tenant->subscription?->plan?->name ?? 'Sin definir' }}</strong></div>
                    <div>Próximo aviso: <strong>{{ $summary['review_notice_at']?->format('d/m/Y') ?? 'Pendiente' }}</strong></div>
                    <div>Upgrade evaluable: <strong>{{ $summary['phase_commitment_ends_at']?->format('d/m/Y') ?? 'Pendiente' }}</strong></div>
                </div>
            </div>
        </div>

        <div class="stats">
            <div class="card">
                <div class="label">Valor total proyecto</div>
                <div class="stat-value">${{ number_format($summary['total_project_value'], 0, ',', '.') }}</div>
            </div>
            <div class="card">
                <div class="label">Abonado al proyecto</div>
                <div class="stat-value" style="color:var(--amr-success);">${{ number_format($summary['paid_toward_project_total'], 0, ',', '.') }}</div>
            </div>
            <div class="card">
                <div class="label">Saldo pendiente</div>
                <div class="stat-value" style="color:var(--amr-warning);">${{ number_format($summary['remaining_project_balance'], 0, ',', '.') }}</div>
            </div>
            <div class="card">
                <div class="label">Mensualidad operativa</div>
                <div class="stat-value">${{ number_format($summary['operational_monthly_fee'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="card" style="margin-bottom:20px;">
            <div class="label">Control de fase</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-top:16px; font-size:13px;">
                <div>Valor de la fase actual:<br><strong>${{ number_format($summary['current_phase_value'], 0, ',', '.') }}</strong></div>
                <div>Abonado en la fase actual:<br><strong>${{ number_format($summary['paid_in_current_phase'], 0, ',', '.') }}</strong></div>
                <div>Saldo de la fase actual:<br><strong>${{ number_format($summary['remaining_current_phase_balance'], 0, ',', '.') }}</strong></div>
                <div>Inicio de fase:<br><strong>{{ $summary['phase_started_at']?->format('d/m/Y') ?? 'Pendiente' }}</strong></div>
                <div>Fin mínimo de permanencia:<br><strong>{{ $summary['phase_commitment_ends_at']?->format('d/m/Y') ?? 'Pendiente' }}</strong></div>
                <div>Aviso de 15 días:<br><strong>{{ $summary['review_notice_at']?->format('d/m/Y') ?? 'Pendiente' }}</strong></div>
                <div>Propuesta vigente:<br><strong>{{ $summary['proposal_reference'] ?? 'Sin referencia cargada' }}</strong></div>
                <div>Compromiso mínimo:<br><strong>{{ $summary['minimum_commitment_months'] }} meses</strong></div>
            </div>
            @if(filled($summary['notes']))
                <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--amr-border); font-size:13px; color:var(--amr-text-secondary);">
                    {{ $summary['notes'] }}
                </div>
            @endif
        </div>

        <div class="card">
            <div class="label">Historial de pagos</div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Tipo</th>
                        <th>Fase</th>
                        <th>Factura</th>
                        <th>Proyecto</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['payments'] as $payment)
                        <tr>
                            <td class="mono">{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <strong>{{ $payment->description }}</strong>
                                @if($payment->notes)
                                    <div style="font-size:11px; color:var(--amr-text-secondary); margin-top:4px;">{{ $payment->notes }}</div>
                                @endif
                            </td>
                            <td>{{ $payment->typeLabel() }}</td>
                            <td>{{ $payment->phase ? 'F' . $payment->phase : '—' }}</td>
                            <td class="mono">{{ $payment->invoice_number ?? 'Pendiente' }}</td>
                            <td>
                                @if($payment->counts_toward_project_total)
                                    <span class="badge badge-success">Abona</span>
                                @else
                                    <span class="badge badge-warning">Operativo</span>
                                @endif
                            </td>
                            <td><strong>${{ number_format($payment->amount, 0, ',', '.') }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; color:var(--amr-text-secondary);">No hay pagos registrados aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
