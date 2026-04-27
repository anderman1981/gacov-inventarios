<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\TenantContext;
use App\Http\Requests\StoreRouteScheduleAssignmentRequest;
use App\Models\Route as ClientRoute;
use App\Models\User;
use App\Support\Routes\RouteScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RouteScheduleCalendarController extends Controller
{
    public function __construct(
        private readonly RouteScheduleService $routeScheduleService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $weekStart = $this->routeScheduleService->weekStart($request->string('week_start')->toString());
        $tenantId = $this->currentTenantId();

        $conductors = User::query()
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->role('conductor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $routes = ClientRoute::query()
            ->with(['driver'])
            ->withCount('machines')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $calendarDays = $this->routeScheduleService->calendarDays($routes, $conductors, $weekStart);

        return view('operations.routes.calendar', compact('weekStart', 'conductors', 'calendarDays'));
    }

    public function store(StoreRouteScheduleAssignmentRequest $request): RedirectResponse
    {
        $route = ClientRoute::query()
            ->whereKey($request->integer('route_id'))
            ->where('is_active', true)
            ->firstOrFail();

        $driver = $request->filled('target_driver_id')
            ? User::query()
                ->where('tenant_id', $request->user()?->tenant_id)
                ->whereKey($request->integer('target_driver_id'))
                ->first()
            : null;

        $this->routeScheduleService->scheduleRouteForDate(
            route: $route,
            date: $request->date('assignment_date'),
            driver: $driver,
            actor: $request->user(),
        );

        $weekStart = $this->routeScheduleService->weekStart($request->string('week_start')->toString());

        return redirect()
            ->route('operations.routes.calendar', ['week_start' => $weekStart->toDateString()])
            ->with('success', 'Programación de ruta actualizada.');
    }

    private function currentTenantId(): ?int
    {
        return $this->tenantContext->getTenantId() ?? auth()->user()?->tenant_id;
    }
}
