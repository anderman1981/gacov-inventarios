<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $fallback = $this->fallbackPathFor($user);
        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $this->canUseIntendedPath($user, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->to($fallback);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function fallbackPathFor(?User $user): string
    {
        if ($user?->hasRole('conductor')) {
            return route('driver.dashboard', absolute: false);
        }

        return route('dashboard', absolute: false);
    }

    private function canUseIntendedPath(?User $user, string $intended): bool
    {
        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return false;
        }

        if (str_starts_with($path, '/driver')) {
            return $user?->isSuperAdmin() || $user?->can('dashboard.own');
        }

        if (str_starts_with($path, '/super-admin')) {
            return $user?->isSuperAdmin() ?? false;
        }

        return true;
    }
}
