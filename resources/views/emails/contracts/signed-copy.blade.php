<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato firmado</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; color: #111827; margin: 0; padding: 24px; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 28px; }
        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        h1 { font-size: 24px; margin: 14px 0 10px; }
        p { line-height: 1.7; margin: 10px 0; color: #374151; }
        .meta { margin-top: 18px; padding: 14px 16px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Contrato firmado</span>
        <h1>Se adjunta la copia final del contrato {{ $contract->contract_number }}</h1>
        <p>El documento ya quedó firmado y bloqueado. En este correo encuentras la copia oficial en PDF.</p>
        <div class="meta">
            <p><strong>Cliente:</strong> {{ $contract->client_company_name }}</p>
            <p><strong>Firmado por:</strong> {{ $contract->client_signer_name ?? $contract->client_legal_representative }}</p>
            <p><strong>Fecha de firma:</strong> {{ optional($contract->client_signed_at)->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
