<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Query\Dashboard\GetDashboardOverview;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(GetDashboardOverview $query): View|RedirectResponse
    {
        $user = auth()->user();

        // Conductores van directo a su dashboard operativo
        if ($user?->hasRole('conductor')) {
            return redirect()->route('driver.dashboard');
        }

        $data = $query->handle();
        $data['userRole'] = $user?->roles->first()?->name ?? 'guest';

        return view('dashboard', $data);
    }
}
