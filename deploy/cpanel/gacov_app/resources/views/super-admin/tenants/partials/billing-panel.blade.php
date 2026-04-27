@php
    $currentPhase = $billingSummary['current_phase'];
    $phaseLabel = $billingSummary['current_phase_label'];
    $reviewNoticeAt = $billingSummary['review_notice_at'];
    $phaseCommitmentEndsAt = $billingSummary['phase_commitment_ends_at'];
@endphp

<div class="card" style="padding:24px; grid-column:1/-1;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px;">
        <div>
            <h3 style="font-size:14px; font-weight:600; color:var(--amr-primary); margin-bottom:6px;">Control financiero por fases</h3>
            <p style="color:var(--amr-text-muted); font-size:12px; line-height:1.5;">
                Uso interno AMR Tech. Desde aquí defines la fase operativa activa del cliente, escalas su acceso por módulos y mantienes el control financiero asociado.
            </p>
        </div>
        <a href="{{ route('super-admin.tenants.billing-report', $tenant) }}" class="btn-secondary">
            Ver reporte
        </a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:22px;">
        <div style="background:rgba(0,212,255,.08); border:1px solid rgba(0,212,255,.18); border-radius:12px; padding:16px;">
            <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Fase operativa activa</div>
            <div style="font-size:22px; font-weight:700; color:var(--amr-primary); margin-top:6px;">F{{ $currentPhase }}</div>
            <div style="font-size:13px; color:var(--amr-text-secondary); margin-top:4px;">{{ $phaseLabel }}</div>
        </div>
        <div style="background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.18); border-radius:12px; padding:16px;">
            <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Abonado al proyecto</div>
            <div style="font-size:22px; font-weight:700; color:var(--amr-success); margin-top:6px;">${{ number_format($billingSummary['paid_toward_project_total'], 0, ',', '.') }}</div>
            <div style="font-size:13px; color:var(--amr-text-secondary); margin-top:4px;">Facturas: {{ $billingSummary['invoice_count'] }}</div>
        </div>
        <div style="background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.18); border-radius:12px; padding:16px;">
            <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Saldo proyecto</div>
            <div style="font-size:22px; font-weight:700; color:var(--amr-warning); margin-top:6px;">${{ number_format($billingSummary['remaining_project_balance'], 0, ',', '.') }}</div>
            <div style="font-size:13px; color:var(--amr-text-secondary); margin-top:4px;">Tope: ${{ number_format($billingSummary['total_project_value'], 0, ',', '.') }}</div>
        </div>
        <div style="background:rgba(124,58,237,.08); border:1px solid rgba(124,58,237,.18); border-radius:12px; padding:16px;">
            <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em;">Mensualidad operativa</div>
            <div style="font-size:22px; font-weight:700; color:#A78BFA; margin-top:6px;">${{ number_format($billingSummary['operational_monthly_fee'], 0, ',', '.') }}</div>
            <div style="font-size:13px; color:var(--amr-text-secondary); margin-top:4px;">Solo mientras aplique la fase activa</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:22px;">
        <div style="border:1px solid var(--amr-border); border-radius:12px; padding:18px;">
            <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:12px;">Módulos activos por fase</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                @forelse($activeModules as $module)
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:rgba(16,185,129,.1); color:var(--amr-success); font-size:12px; border:1px solid rgba(16,185,129,.18);">
                        F{{ $module->phase_required }} · {{ $module->name }}
                    </span>
                @empty
                    <span style="font-size:13px; color:var(--amr-text-muted);">Todavía no hay módulos habilitados para este cliente.</span>
                @endforelse
            </div>
        </div>
        <div style="border:1px solid var(--amr-border); border-radius:12px; padding:18px;">
            <div style="font-size:11px; color:var(--amr-text-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:12px;">Próximo desbloqueo sugerido</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                @forelse($nextPhaseModules as $module)
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:rgba(59,130,246,.1); color:#60A5FA; font-size:12px; border:1px solid rgba(59,130,246,.18);">
                        F{{ $module->phase_required }} · {{ $module->name }}
                    </span>
                @empty
                    <span style="font-size:13px; color:var(--amr-text-muted);">
                        {{ $currentPhase >= 5 ? 'El cliente ya tiene la fase máxima habilitada.' : 'No hay módulos adicionales configurados para la siguiente fase.' }}
                    </span>
                @endforelse
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1.2fr .8fr; gap:18px;">
        <div style="display:flex; flex-direction:column; gap:18px;">
            <div style="border:1px solid var(--amr-border); border-radius:12px; padding:18px;">
                <h4 style="font-size:13px; font-weight:600; color:var(--amr-text-primary); margin-bottom:14px;">Configuración de fase activa y control interno</h4>
                <form method="POST" action="{{ route('super-admin.tenants.billing-profile.update', $tenant) }}">
                    @csrf
                    @method('PUT')
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div>
                            <label class="form-label">Fase operativa activa</label>
                            <select name="current_phase" class="form-input">
                                @foreach(\App\Models\TenantBillingProfile::PHASE_LABELS as $phaseNumber => $label)
                                    <option value="{{ $phaseNumber }}" @selected(old('current_phase', $billingSummary['current_phase']) == $phaseNumber)>
                                        F{{ $phaseNumber }} · {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Valor aprobado de la fase</label>
                            <input type="number" step="0.01" min="0" name="current_phase_value" class="form-input"
                                   value="{{ old('current_phase_value', $billingSummary['current_phase_value']) }}">
                        </div>
                        <div>
                            <label class="form-label">Valor total del proyecto</label>
                            <input type="number" step="0.01" min="0" name="total_project_value" class="form-input"
                                   value="{{ old('total_project_value', $billingSummary['total_project_value']) }}">
                        </div>
                        <div>
                            <label class="form-label">Mínimo de permanencia (meses)</label>
                            <input type="number" min="1" max="24" name="minimum_commitment_months" class="form-input"
                                   value="{{ old('minimum_commitment_months', $billingSummary['minimum_commitment_months']) }}">
                        </div>
                        <div>
                            <label class="form-label">Inicio de la fase</label>
                            <input type="date" name="phase_started_at" class="form-input"
                                   value="{{ old('phase_started_at', $billingSummary['phase_started_at']?->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label class="form-label">Referencia de propuesta</label>
                            <input type="text" name="proposal_reference" class="form-input"
                                   value="{{ old('proposal_reference', $billingSummary['proposal_reference']) }}"
                                   placeholder="Ej: PROP-GACOV-F1-2026-04">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label class="form-label">Observación interna</label>
                            <textarea name="notes" class="form-input" rows="3" placeholder="Notas comerciales, acuerdo de upgrade, decisión del cliente...">{{ old('notes', $billingSummary['notes']) }}</textarea>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:14px; flex-wrap:wrap;">
                        <div style="font-size:12px; color:var(--amr-text-muted); line-height:1.6;">
                            Al guardar esta fase, todos los usuarios del cliente verán y usarán únicamente los módulos habilitados hasta F{{ old('current_phase', $billingSummary['current_phase']) }}.
                            <span style="margin:0 8px;">·</span>
                            Aviso 15 días antes:
                            <strong style="color:var(--amr-text-primary);">{{ $reviewNoticeAt?->format('d/m/Y') ?? 'Pendiente de definir' }}</strong>
                            <span style="margin:0 8px;">·</span>
                            Fin compromiso mínimo:
                            <strong style="color:var(--amr-text-primary);">{{ $phaseCommitmentEndsAt?->format('d/m/Y') ?? 'Pendiente de definir' }}</strong>
                        </div>
                        <button type="submit" class="btn-primary">Guardar control</button>
                    </div>
                </form>
            </div>

            <div style="border:1px solid var(--amr-border); border-radius:12px; padding:18px;">
                <h4 style="font-size:13px; font-weight:600; color:var(--amr-text-primary); margin-bottom:14px;">Pagos registrados</h4>
                @if($billingSummary['payments']->isEmpty())
                    <p style="font-size:13px; color:var(--amr-text-muted);">Todavía no hay pagos cargados para este cliente.</p>
                @else
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Tipo</th>
                                    <th>Fase</th>
                                    <th>Factura</th>
                                    <th>Abona proyecto</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($billingSummary['payments'] as $payment)
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:12px;">{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                                        <td>
                                            <div style="font-weight:600;">{{ $payment->description }}</div>
                                            @if($payment->notes)
                                                <div style="font-size:11px; color:var(--amr-text-muted); margin-top:2px;">{{ $payment->notes }}</div>
                                            @endif
                                        </td>
                                        <td style="font-size:12px;">{{ $payment->typeLabel() }}</td>
                                        <td>{{ $payment->phase ? 'F' . $payment->phase : '—' }}</td>
                                        <td style="font-family:var(--font-mono); font-size:12px;">{{ $payment->invoice_number ?? 'Pendiente' }}</td>
                                        <td>
                                            @if($payment->counts_toward_project_total)
                                                <span class="badge-success">Sí</span>
                                            @else
                                                <span class="badge-warning">No</span>
                                            @endif
                                        </td>
                                        <td style="font-weight:700;">${{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:18px;">
            <div style="border:1px solid var(--amr-border); border-radius:12px; padding:18px;">
                <h4 style="font-size:13px; font-weight:600; color:var(--amr-text-primary); margin-bottom:14px;">Registrar pago</h4>
                <form method="POST" action="{{ route('super-admin.tenants.payments.store', $tenant) }}">
                    @csrf
                    <div style="display:grid; grid-template-columns:1fr; gap:12px;">
                        <div>
                            <label class="form-label">Tipo de pago</label>
                            <select name="type" class="form-input">
                                @foreach($paymentTypes as $paymentType => $paymentLabel)
                                    <option value="{{ $paymentType }}" @selected(old('type') === $paymentType)>{{ $paymentLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Concepto</label>
                            <input type="text" name="description" class="form-input" value="{{ old('description') }}"
                                   placeholder="Ej: Pago inicial 50% Fase 1">
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <div>
                                <label class="form-label">Fase aplicada</label>
                                <select name="phase" class="form-input">
                                    @foreach(\App\Models\TenantBillingProfile::PHASE_LABELS as $phaseNumber => $label)
                                        <option value="{{ $phaseNumber }}" @selected(old('phase', $billingSummary['current_phase']) == $phaseNumber)>
                                            F{{ $phaseNumber }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Fecha de pago</label>
                                <input type="date" name="paid_at" class="form-input" value="{{ old('paid_at', now()->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <div>
                                <label class="form-label">Valor pagado</label>
                                <input type="number" min="0.01" step="0.01" name="amount" class="form-input" value="{{ old('amount') }}">
                            </div>
                            <div>
                                <label class="form-label">Factura / referencia</label>
                                <input type="text" name="invoice_number" class="form-input" value="{{ old('invoice_number') }}"
                                       placeholder="FAC-2026-001">
                            </div>
                        </div>
                        <div>
                            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:var(--amr-text-secondary);">
                                <input type="checkbox" name="counts_toward_project_total" value="1"
                                       @checked(old('counts_toward_project_total'))>
                                Este pago abona al valor total del proyecto
                            </label>
                        </div>
                        <div>
                            <label class="form-label">Observación</label>
                            <textarea name="notes" class="form-input" rows="3" placeholder="Detalle de factura, acuerdo, soporte o ajuste">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn-primary" style="justify-content:center;">Registrar pago</button>
                    </div>
                </form>
            </div>

            <div style="border:1px solid var(--amr-border); border-radius:12px; padding:18px;">
                <h4 style="font-size:13px; font-weight:600; color:var(--amr-text-primary); margin-bottom:12px;">Resumen de control</h4>
                <div style="display:flex; flex-direction:column; gap:10px; font-size:13px;">
                    <div style="display:flex; justify-content:space-between; gap:12px;">
                        <span style="color:var(--amr-text-muted);">Valor fase actual</span>
                        <strong>${{ number_format($billingSummary['current_phase_value'], 0, ',', '.') }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; gap:12px;">
                        <span style="color:var(--amr-text-muted);">Abonado en fase actual</span>
                        <strong>${{ number_format($billingSummary['paid_in_current_phase'], 0, ',', '.') }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; gap:12px;">
                        <span style="color:var(--amr-text-muted);">Saldo fase actual</span>
                        <strong>${{ number_format($billingSummary['remaining_current_phase_balance'], 0, ',', '.') }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; gap:12px;">
                        <span style="color:var(--amr-text-muted);">Mensualidades operativas registradas</span>
                        <strong>${{ number_format($billingSummary['operations_paid_total'], 0, ',', '.') }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; gap:12px;">
                        <span style="color:var(--amr-text-muted);">Extras registrados</span>
                        <strong>${{ number_format($billingSummary['extra_paid_total'], 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--amr-border); display:flex; flex-direction:column; gap:10px;">
                    <div style="font-size:12px; color:var(--amr-text-muted); line-height:1.6;">
                        @if($billingSummary['review_due'])
                            <span style="color:var(--amr-warning); font-weight:700;">Aviso pendiente:</span> ya corresponde reorganizar propuesta y plan de pagos.
                        @elseif($reviewNoticeAt)
                            Próximo aviso comercial: <strong style="color:var(--amr-text-primary);">{{ $reviewNoticeAt->format('d/m/Y') }}</strong>
                        @else
                            Define el inicio de fase para calcular el aviso de 15 días antes.
                        @endif
                    </div>
                    <div style="font-size:12px; color:var(--amr-text-muted); line-height:1.6;">
                        @if($billingSummary['upgrade_window_open'])
                            <span style="color:var(--amr-success); font-weight:700;">Ventana abierta:</span> ya se puede revisar upgrade de fase.
                        @elseif($phaseCommitmentEndsAt)
                            Upgrade evaluable desde: <strong style="color:var(--amr-text-primary);">{{ $phaseCommitmentEndsAt->format('d/m/Y') }}</strong>
                        @else
                            Aún no se ha definido la fecha mínima para upgrade.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
