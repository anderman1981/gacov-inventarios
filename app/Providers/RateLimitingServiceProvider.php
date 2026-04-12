<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class RateLimitingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No register logic needed - rate limiting config is in boot()
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // ============================================================
        // RATE LIMITING - GACOV INVENTARIOS
        // Configuración de seguridad anti-brute force
        // ============================================================

        // Limitador para autenticación (login)
        // 5 intentos por minuto por IP + email
        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by($request->ip().'|'.$request->input('email', ''))
                ->response(function (Request $request): Response {
                    return response('Demasiados intentos de inicio de sesión. Por favor espera un minuto.', 429);
                });
        });

        // Limitador para registro
        // 3 registros por hora por IP
        RateLimiter::for('register', function (Request $request): Limit {
            return Limit::perHour(3)
                ->by($request->ip())
                ->response(function (Request $request): Response {
                    return response('Demasiados registros desde esta IP. Por favor intenta más tarde.', 429);
                });
        });

        // Limitador para solicitud de reset de password
        // 3 solicitudes por hora por email
        RateLimiter::for('password-reset', function (Request $request): Limit {
            return Limit::perHour(3)
                ->by($request->input('email', ''))
                ->response(function (Request $request): Response {
                    return response('Demasiadas solicitudes de recuperación de contraseña. Por favor espera una hora.', 429);
                });
        });

        // Limitador general de API (si se habilita en el futuro)
        // 60 requests por minuto por usuario
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request): JsonResponse {
                    return response()->json([
                        'error' => 'Demasiadas solicitudes. Por favor reduce la frecuencia.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // Limitador para operaciones de inventario (evitar abuse)
        // 30 operaciones por minuto por usuario
        RateLimiter::for('inventory-operations', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request): Response {
                    return response('Demasiadas operaciones de inventario. Por favor espera.', 429);
                });
        });

        // Limitador para importaciones (operaciones pesadas)
        // 5 importaciones por hora
        RateLimiter::for('imports', function (Request $request): Limit {
            return Limit::perHour(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request): Response {
                    return response('Demasiadas importaciones. Por favor espera una hora.', 429);
                });
        });
    }
}
