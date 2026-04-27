@extends('layouts.app')

@section('title', 'Actualizar plan')

@section('content')
<div style="max-width:900px; margin:0 auto; padding:40px 24px;">

    <div style="text-align:center; margin-bottom:48px;">
        <div style="font-size:48px; margin-bottom:16px;">🚀</div>
        <h1 style="font-family:var(--font-display, 'Space Grotesk'); font-size:28px; font-weight:700; margin-bottom:8px;">
            Activa la siguiente fase del cliente
        </h1>
        <p style="color:var(--amr-text-secondary, #9CA3AF); font-size:15px;">
            La funcionalidad que intentas usar está fuera de la fase activa de este cliente. Escala la fase desde super admin o sincroniza el plan comercial correspondiente.
        </p>
    </div>

    @if(isset($requiredModule))
    <div style="background:rgba(239,68,68,.06); border:1px solid rgba(239,68,68,.2); border-radius:12px; padding:16px 20px; margin-bottom:32px; text-align:center;">
        <strong style="color:#EF4444;">Módulo bloqueado:</strong>
        <span style="color:#F9FAFB; margin-left:8px;">{{ $requiredModule }}</span>
        @if(isset($requiredPhase))
            <div style="margin-top:8px; color:var(--amr-text-secondary, #9CA3AF); font-size:13px;">
                Requiere fase <strong>F{{ $requiredPhase }}</strong>. Fase actual del cliente:
                <strong>F{{ $currentPhase ?? 1 }}</strong>.
            </div>
        @endif
    </div>
    @endif

    {{-- Plans grid --}}
    @if(isset($plans) && $plans->count())
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:40px;">
        @foreach($plans as $plan)
        @php $isCurrent = isset($currentPlan) && $currentPlan?->id === $plan->id; @endphp
        <div style="
            background: {{ $isCurrent ? 'rgba(0,212,255,.05)' : 'var(--amr-bg-surface, #111827)' }};
            border: 1px solid {{ $isCurrent ? 'rgba(0,212,255,.4)' : 'var(--amr-border, #1F2937)' }};
            border-radius: 16px;
            padding: 24px;
            position:relative;
        ">
            @if($isCurrent)
            <div style="position:absolute; top:-10px; left:50%; transform:translateX(-50%); background:var(--amr-primary, #00D4FF); color:#0A0E1A; font-size:11px; font-weight:700; padding:3px 12px; border-radius:20px; white-space:nowrap;">
                Plan actual
            </div>
            @endif

            <div style="font-size:12px; color:var(--amr-text-muted, #6B7280); text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px;">
                Fase {{ $loop->iteration }}
            </div>
            <div style="font-family:var(--font-display, 'Space Grotesk'); font-size:20px; font-weight:700; margin-bottom:8px;">
                {{ $plan->name }}
            </div>
            <div style="font-size:22px; font-weight:700; color:var(--amr-primary, #00D4FF); margin-bottom:16px;">
                ${{ number_format($plan->monthly_price, 0, ',', '.') }}
                <span style="font-size:13px; font-weight:400; color:var(--amr-text-secondary, #9CA3AF)">/mes</span>
            </div>

            @if(is_array($plan->modules) && count($plan->modules))
            <ul style="list-style:none; margin-bottom:20px;">
                @foreach(array_slice($plan->modules, 0, 6) as $mod)
                <li style="font-size:12px; color:var(--amr-text-secondary, #9CA3AF); padding:4px 0; display:flex; align-items:center; gap:6px;">
                    <span style="color:#10B981;">✓</span> {{ $mod }}
                </li>
                @endforeach
                @if(count($plan->modules) > 6)
                <li style="font-size:11px; color:var(--amr-text-muted, #6B7280); padding-top:4px;">
                    +{{ count($plan->modules) - 6 }} módulos más
                </li>
                @endif
            </ul>
            @endif

            @if(!$isCurrent)
            <a href="mailto:{{ config('mail.from.address', 'sistema@gacov.com.co') }}?subject=Actualizar a {{ $plan->name }}"
               style="display:block; text-align:center; background:linear-gradient(135deg,#00D4FF 0%,#7C3AED 100%); color:white; font-weight:600; font-size:13px; padding:10px; border-radius:8px; text-decoration:none;">
                Actualizar a {{ $plan->name }}
            </a>
            @else
            <div style="text-align:center; padding:10px; border-radius:8px; border:1px solid rgba(0,212,255,.3); color:var(--amr-primary, #00D4FF); font-size:13px; font-weight:500;">
                Tu plan actual
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div style="text-align:center;">
        <a href="{{ url()->previous() }}"
           style="color:var(--amr-text-secondary, #9CA3AF); font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            ← Volver atrás
        </a>
    </div>

</div>
@endsection
