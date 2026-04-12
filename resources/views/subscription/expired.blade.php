<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suscripción vencida — {{ config('app.name') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --amr-primary: #00D4FF; --amr-bg-base: #0A0E1A;
    --amr-bg-surface: #111827; --amr-border: #1F2937;
    --amr-text-primary: #F9FAFB; --amr-text-secondary: #9CA3AF;
    --amr-error: #EF4444; --amr-warning: #F59E0B;
    --font-sans: 'Inter', system-ui, sans-serif;
    --font-display: 'Space Grotesk', 'Inter', sans-serif;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body {
    font-family: var(--font-sans);
    background: var(--amr-bg-base);
    color: var(--amr-text-primary);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px;
  }
  .container {
    max-width: 480px;
    text-align: center;
  }
  .icon {
    font-size: 64px;
    margin-bottom: 24px;
    display: block;
  }
  .title {
    font-family: var(--font-display);
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--amr-warning);
  }
  .subtitle {
    font-size: 15px;
    color: var(--amr-text-secondary);
    line-height: 1.6;
    margin-bottom: 32px;
  }
  .card {
    background: var(--amr-bg-surface);
    border: 1px solid var(--amr-border);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    text-align: left;
  }
  .plan-name {
    font-family: var(--font-display);
    font-size: 20px;
    font-weight: 700;
    color: var(--amr-primary);
  }
  .plan-detail {
    font-size: 13px;
    color: var(--amr-text-secondary);
    margin-top: 4px;
  }
  .btn-primary {
    display: inline-block;
    background: linear-gradient(135deg, #00D4FF 0%, #7C3AED 100%);
    color: white;
    font-weight: 600;
    font-size: 15px;
    padding: 14px 32px;
    border-radius: 10px;
    text-decoration: none;
    margin-bottom: 16px;
    width: 100%;
    text-align: center;
  }
  .btn-ghost {
    display: inline-block;
    color: var(--amr-text-secondary);
    font-size: 13px;
    text-decoration: none;
    padding: 8px;
  }
  .contact {
    font-size: 13px;
    color: var(--amr-text-secondary);
  }
  .contact a { color: var(--amr-primary); text-decoration: none; }
</style>
</head>
<body>
<div class="container">
    <span class="icon">⏰</span>
    <h1 class="title">Suscripción vencida</h1>
    <p class="subtitle">
        Tu período de acceso ha terminado. Para continuar usando el sistema,
        renueva tu suscripción o contacta a tu administrador.
    </p>

    @if(isset($tenant) && $tenant->subscription)
    <div class="card">
        <div class="plan-name">{{ $tenant->subscription->plan->name ?? 'Plan actual' }}</div>
        <div class="plan-detail">
            Venció el {{ $tenant->subscription->current_period_end?->format('d/m/Y') ?? '—' }}
        </div>
    </div>
    @endif

    <a href="mailto:{{ config('mail.from.address', 'sistema@gacov.com.co') }}?subject=Renovar suscripción" class="btn-primary">
        📧 Contactar para renovar
    </a>

    <div>
        <a href="{{ route('login') }}" class="btn-ghost">← Volver al inicio</a>
    </div>

    <div class="contact" style="margin-top:32px;">
        <strong>AMR Tech</strong> · Soporte: <a href="mailto:soporte@amrtech.co">soporte@amrtech.co</a>
    </div>
</div>
</body>
</html>
