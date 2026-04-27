<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Tenant\Services\TenantContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        // @moduleEnabled('key') ... @endmoduleEnabled
        Blade::directive('moduleEnabled', function (string $expression): string {
            return "<?php if(app(\App\Domain\Tenant\Services\TenantContext::class)->canAccessModule($expression)): ?>";
        });

        Blade::directive('endmoduleEnabled', fn (): string => '<?php endif; ?>');

        // @moduleDisabled('key') ... @endmoduleDisabled
        Blade::directive('moduleDisabled', function (string $expression): string {
            return "<?php if(!app(\App\Domain\Tenant\Services\TenantContext::class)->canAccessModule($expression)): ?>";
        });

        Blade::directive('endmoduleDisabled', fn (): string => '<?php endif; ?>');

        // @phase(3) ... @endphase  (fase mínima requerida)
        Blade::directive('phase', function (string $expression): string {
            return "<?php if(app(\App\Domain\Tenant\Services\TenantContext::class)->currentPhase() >= $expression): ?>";
        });

        Blade::directive('endphase', fn (): string => '<?php endif; ?>');
    }
}
