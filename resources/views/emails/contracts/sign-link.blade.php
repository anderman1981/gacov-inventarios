<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma pendiente</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; color: #111827; margin: 0; padding: 24px; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 28px; }
        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        h1 { font-size: 24px; margin: 14px 0 10px; }
        p { line-height: 1.7; margin: 10px 0; color: #374151; }
        .btn { display: inline-block; margin-top: 18px; padding: 14px 18px; border-radius: 12px; background: #111827; color: #fff; text-decoration: none; font-weight: 700; }
        .muted { color: #6b7280; font-size: 13px; }
        .meta { margin-top: 18px; padding: 14px 16px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Contrato pendiente de firma</span>
        <h1>Firma el contrato {{ $contract->contract_number }}</h1>
        <p>Ya está listo el contrato entre <strong>{{ $contract->provider_name }}</strong> y <strong>{{ $contract->client_company_name }}</strong>.</p>
        <p>Para revisar el documento, firmarlo digitalmente y recibir la copia final en PDF, abre el enlace seguro de abajo.</p>
        <p><a href="{{ $signingUrl }}" class="btn">Abrir contrato para firmar</a></p>
        <div class="meta">
            <p><strong>Cliente:</strong> {{ $contract->client_company_name }}</p>
            <p><strong>Representante:</strong> {{ $contract->client_legal_representative }}</p>
            <p><strong>Fecha:</strong> {{ $contract->contract_date->format('d/m/Y') }}</p>
        </div>
        <p class="muted">Si no reconoces este mensaje, puedes ignorarlo. El enlace expira automáticamente.</p>
    </div>
</body>
</html>
