@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="auth-logo">
    <img src="{{ asset('images/logo.jpg') }}" alt="GACOV" loading="eager" decoding="async">
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
