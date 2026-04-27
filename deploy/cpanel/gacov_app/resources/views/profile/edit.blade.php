@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
<div class="page-header">
    <h1 class="page-title">Mi perfil</h1>
    <p class="page-subtitle">Actualiza tu información personal y la seguridad de tu cuenta.</p>
</div>

<div style="display:grid;gap:var(--space-6);">
    <section class="panel">
        <div class="panel-header">
            <span class="panel-title">Información personal</span>
        </div>
        <div class="panel-body">
            @include('profile.partials.update-profile-information-form')
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <span class="panel-title">Cambiar contraseña</span>
        </div>
        <div class="panel-body">
            @include('profile.partials.update-password-form')
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <span class="panel-title">Eliminar cuenta</span>
        </div>
        <div class="panel-body">
            @include('profile.partials.delete-user-form')
        </div>
    </section>
</div>
@endsection
