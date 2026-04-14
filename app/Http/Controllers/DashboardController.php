<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Query\Dashboard\GetDashboardOverview;
use App\Notifications\InventoryAdjustmentNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
        $data['inventoryNotifications'] = collect();

        if (
            $user !== null
            && $user->hasAnyRole(['admin', 'super_admin'])
            && Schema::hasTable('notifications')
        ) {
            /** @var Collection<int, mixed> $notifications */
            $notifications = $user->unreadNotifications()
                ->where('type', InventoryAdjustmentNotification::class)
                ->latest()
                ->limit(5)
                ->get();

            $data['inventoryNotifications'] = $notifications;
        }

        return view('dashboard', $data);
    }
}
