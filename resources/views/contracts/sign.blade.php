<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firmar contrato {{ $contract->contract_number }}</title>
    <style>
        :root {
            --bg: #f4f6f8;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --border: #d7dee8;
            --text: #111827;
            --muted: #6b7280;
            --accent: #ef4444;
            --accent-dark: #111827;
            --success: #16a34a;
            --radius: 20px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, system-ui, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        .hero {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 18px;
        }
        .hero h1 { margin: 6px 0 4px; font-size: clamp(26px, 4vw, 40px); }
        .hero p { margin: 0; color: var(--muted); }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(239,68,68,.12);
            color: #b91c1c;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 18px;
            align-items: start;
        }
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 18px 60px rgba(15, 23, 42, .06);
            overflow: hidden;
        }
        .panel-head {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }
        .panel-body { padding: 18px; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .summary-grid div {
            border: 1px solid var(--border);
            border-radius: 16px;
            background: var(--surface-soft);
            padding: 12px 14px;
        }
        .summary-grid span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }
        .summary-grid strong { font-size: 14px; }
        .sign-box {
            margin-top: 16px;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 14px;
            background: var(--surface-soft);
        }
        .sign-box label { display:block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
        .sign-box input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 14px;
            background: #fff;
        }
        .signature-wrap {
            margin-top: 16px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #fff;
            overflow: hidden;
        }
        .signature-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            background: #fff;
        }
        .signature-toolbar strong { font-size: 14px; }
        .signature-pad {
            width: 100%;
            height: 220px;
            display: block;
            background:
                linear-gradient(180deg, rgba(17,24,39,.02), rgba(17,24,39,.02)),
                repeating-linear-gradient(0deg, #fff, #fff 29px, rgba(148,163,184,.08) 30px);
            touch-action: none;
        }
        .signature-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 11px 16px;
            border-radius: 14px;
            border: 1px solid transparent;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-secondary { background: #fff; border-color: var(--border); color: var(--text); }
        .btn-dark { background: var(--accent-dark); color: #fff; }
        .contract-frame {
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
        }
        .contract-frame .contract-paper {
            padding: 22px;
            max-width: none;
            margin: 0;
            border: 0;
            box-shadow: none;
            border-radius: 0;
        }
        .sidebar-note {
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(239,68,68,.16);
            background: rgba(239,68,68,.06);
            color: #7f1d1d;
            font-size: 13px;
            line-height: 1.6;
        }
        .locked-box {
            padding: 16px;
            border-radius: 18px;
            background: rgba(22,163,74,.08);
            border: 1px solid rgba(22,163,74,.2);
            color: #14532d;
            margin-bottom: 14px;
        }
        .mobile-actions {
            display: none;
        }
        @media (max-width: 1080px) {
            .layout { grid-template-columns: 1fr; }
            .mobile-actions { display: flex; }
        }
        @media (max-width: 720px) {
            .hero, .panel-head { flex-direction: column; align-items: flex-start; }
            .summary-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <div>
            <span class="pill">Firma digital</span>
            <h1>Contrato {{ $contract->contract_number }}</h1>
            <p>Revisa el documento, firma con tu dedo o mouse y recibe tu copia final por correo.</p>
        </div>
            <div class="mobile-actions">
            <a href="{{ $pdfUrl }}" class="btn btn-secondary">Descargar PDF</a>
        </div>
    </div>

    @if(session('success'))
    <div class="locked-box">{{ session('success') }}</div>
    @endif

    <div class="layout">
        <section class="panel">
            <div class="panel-head">
                <strong>Documento interactivo</strong>
                @if($isLocked)
                    <span class="pill" style="background:rgba(22,163,74,.12);color:#166534">Bloqueado</span>
                @else
                    <span class="pill">Pendiente de firma</span>
                @endif
            </div>
            <div class="panel-body">
                <div class="contract-frame">
                    @include('contracts.partials.document')
                </div>
            </div>
        </section>

        <aside class="panel">
            <div class="panel-head">
                <strong>Acción de firma</strong>
            </div>
            <div class="panel-body">
                <div class="summary-grid">
                    <div><span>Cliente</span><strong>{{ $contract->client_company_name }}</strong></div>
                    <div><span>Representante</span><strong>{{ $contract->client_legal_representative }}</strong></div>
                    <div><span>Correo</span><strong>{{ $contract->client_email }}</strong></div>
                    <div><span>Estado</span><strong>{{ $contract->status_label }}</strong></div>
                </div>

                @if($isLocked)
                    <div class="locked-box" style="margin-top:16px">
                        El contrato ya quedó firmado y bloqueado. Solo puedes descargar el PDF o imprimirlo.
                    </div>
                    <div class="signature-actions">
                        <a href="{{ $pdfUrl }}" class="btn btn-secondary">Descargar PDF</a>
                        <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir</button>
                    </div>
                @else
                    <form method="POST" action="{{ $signingUrl }}">
                        @csrf
                        <div class="sign-box">
                            <label for="client_signer_name">Nombre de quien firma</label>
                            <input id="client_signer_name" name="client_signer_name" type="text" value="{{ old('client_signer_name', $contract->client_legal_representative) }}" required>
                        </div>

                        <div class="sign-box">
                            <label for="client_signer_document">Documento de quien firma</label>
                            <input id="client_signer_document" name="client_signer_document" type="text" value="{{ old('client_signer_document', $contract->client_legal_representative_document) }}" required>
                        </div>

                        <div class="signature-wrap">
                            <div class="signature-toolbar">
                                <strong>Firma digital</strong>
                                <button type="button" class="btn btn-secondary" id="clear-signature">Limpiar</button>
                            </div>
                            <canvas id="signature-pad" class="signature-pad"></canvas>
                            <input type="hidden" name="client_signature" id="client_signature">
                        </div>

                        <div class="signature-actions">
                            <button type="submit" class="btn btn-primary">Firmar y bloquear</button>
                            <a href="{{ $pdfUrl }}" class="btn btn-secondary">Ver PDF</a>
                            <button type="button" class="btn btn-secondary" onclick="window.print()">Imprimir</button>
                        </div>
                    </form>
                @endif

                <div class="sidebar-note" style="margin-top:16px">
                    Al firmar, el contrato queda bloqueado, se genera la copia en PDF y se envía al proveedor y al cliente.
                </div>
            </div>
        </aside>
    </div>
</div>

@if(! $isLocked)
<script>
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signature-pad');
    const hidden = document.getElementById('client_signature');
    const clearButton = document.getElementById('clear-signature');
    if (!canvas || !hidden) return;

    const context = canvas.getContext('2d');
    const resizeCanvas = () => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        context.scale(ratio, ratio);
        context.lineWidth = 2.5;
        context.lineJoin = 'round';
        context.lineCap = 'round';
        context.strokeStyle = '#111827';
    };

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    let drawing = false;
    let lastX = 0;
    let lastY = 0;

    const getPoint = (event) => {
        const rect = canvas.getBoundingClientRect();
        const point = event.touches ? event.touches[0] : event;
        return {
            x: point.clientX - rect.left,
            y: point.clientY - rect.top,
        };
    };

    const start = (event) => {
        drawing = true;
        const { x, y } = getPoint(event);
        [lastX, lastY] = [x, y];
    };

    const draw = (event) => {
        if (!drawing) return;
        event.preventDefault();
        const { x, y } = getPoint(event);
        context.beginPath();
        context.moveTo(lastX, lastY);
        context.lineTo(x, y);
        context.stroke();
        [lastX, lastY] = [x, y];
        hidden.value = canvas.toDataURL('image/png');
    };

    const stop = () => {
        drawing = false;
        hidden.value = canvas.toDataURL('image/png');
    };

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stop);
    canvas.addEventListener('mouseleave', stop);

    canvas.addEventListener('touchstart', start, { passive: true });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stop);

    clearButton?.addEventListener('click', () => {
        context.clearRect(0, 0, canvas.width, canvas.height);
        hidden.value = '';
    });
});
</script>
@endif
</body>
</html>
