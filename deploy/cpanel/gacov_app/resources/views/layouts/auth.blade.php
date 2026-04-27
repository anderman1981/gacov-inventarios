<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Iniciar Sesión') — Inversiones GACOV S.A.S.</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#D71920">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    @php($hasViteAssets = file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
    @if($hasViteAssets)
    @vite(['frontend/resources/css/app.css', 'frontend/resources/js/app.js'])
    @endif
</head>
<body class="auth-page">
    <div class="auth-wrapper">
        <div>
            <div class="auth-card">
                @yield('content')
            </div>
            <div class="auth-footer">
                Desarrollado por <a href="#" target="_blank">AMR Tech</a>
                &nbsp;·&nbsp; Inversiones GACOV S.A.S. &copy; {{ date('Y') }}
            </div>
        </div>
    </div>
</body>
</html>
