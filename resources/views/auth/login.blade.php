@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="auth-logo">
    {{-- Logo GACOV --}}
    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="48" height="48" rx="12" fill="url(#grad)"/>
        <path d="M14 24C14 18.477 18.477 14 24 14C27.09 14 29.865 15.34 31.778 17.5H27C25.895 17.5 25 18.395 25 19.5C25 20.605 25.895 21.5 27 21.5H35V13.5C35 12.395 34.105 11.5 33 11.5C31.895 11.5 31 12.395 31 13.5V14.82C28.663 12.71 25.48 11.5 22 11.5C14.82 11.5 9 17.32 9 24.5C9 31.68 14.82 37.5 22 37.5C25.82 37.5 29.27 35.91 31.73 33.37C32.51 32.57 32.5 31.29 31.7 30.51C30.9 29.73 29.62 29.74 28.84 30.54C27.05 32.37 24.65 33.5 22 33.5C17.03 33.5 13 29.47 13 24.5L14 24Z" fill="white" opacity=".9"/>
        <defs>
            <linearGradient id="grad" x1="0" y1="0" x2="48" y2="48">
                <stop offset="0%" stop-color="#00D4FF"/>
                <stop offset="100%" stop-color="#7C3AED"/>
            </linearGradient>
        </defs>
    </svg>
    <div>
        <div class="brand-name">Inversiones GACOV S.A.S.</div>
        <div class="brand-sub">Sistema de Inventarios</div>
    </div>
</div>

<h1 class="auth-title">Bienvenido</h1>
<p class="auth-subtitle">Ingresa tus credenciales para continuar</p>

@if ($errors->any())
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:var(--radius-md);padding:var(--space-3) var(--space-4);margin-bottom:var(--space-5);color:#EF4444;font-size:13px;">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="form-group">
        <label class="form-label" for="email">Correo electrónico</label>
        <input
            class="form-input @error('email') is-invalid @enderror"
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="usuario@gacov.com.co"
            autocomplete="email"
            autofocus
            required
        >
        @error('email')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input
            class="form-input @error('password') is-invalid @enderror"
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
        >
        @error('password')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);">
        <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;">
            <input type="checkbox" name="remember" style="accent-color:var(--gacov-primary);">
            <span style="font-size:13px;color:var(--gacov-text-secondary);">Recordarme</span>
        </label>
    </div>

    <button type="submit" class="btn btn-primary">
        Ingresar al sistema
    </button>
</form>
@endsection
