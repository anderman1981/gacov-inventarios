<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Acceso' }} — Inversiones GACOV S.A.S.</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=20260501">
    <link rel="shortcut icon" href="/favicon.ico?v=20260501">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#D71920">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    @include('layouts.partials.vite-assets')
</head>
<body>
    <div class="auth-wrapper">
        <div>
            <div class="auth-card">
                {{ $slot }}
            </div>
            <div class="auth-footer">
                Desarrollado por <a href="#" target="_blank" rel="noreferrer">AMR Tech</a>
                &nbsp;·&nbsp; Inversiones GACOV S.A.S. &copy; {{ date('Y') }}
            </div>
        </div>
    </div>
</body>
</html>
