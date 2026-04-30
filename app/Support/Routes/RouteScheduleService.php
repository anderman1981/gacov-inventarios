<?php

declare(strict_types=1);

namespace App\Support\Routes;

use App\Models\Route as ClientRoute;
use App\Models\RouteScheduleAssignment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class RouteScheduleService
{
    public function weekStart(?string $weekStart = null): Carbon
    {
        if (! filled($weekStart)) {
            return today()->startOfWeek(Carbon::MONDAY);
        }

        try {
            return Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            return today()->startOfWeek(Carbon::MONDAY);
        }
    }

    /**
     * @return Collection<int, Carbon>
     */
    public function weekDays(CarbonInterface $weekStart, int $days = 7): Collection
    {
        return collect(range(0, $days - 1))
            ->map(fn (int $offset): Carbon => Carbon::parse($weekStart)->copy()->addDays($offset));
    }

    /**
     * @return Collection<int, ClientRoute>
     */
    public function availableRoutesForUser(User $user, CarbonInterface $date): Collection
    {
        $routes = ClientRoute::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($this->canManageAnyRoute($user)) {
            return $routes;
        }

        $dailyAssignments = $this->assignmentsForDate($date);
        $fallbackDrivers = $this->fallbackDriversForRoutes($routes);

        return $routes
            ->filter(fn (ClientRoute $route): bool => $this->effectiveDriverId($route, $dailyAssignments, $fallbackDrivers) === $user->id)
            ->values();
    }

    /**
     * @param  Collection<int, ClientRoute>  $routes
     * @param  Collection<int, User>  $conductors
     * @return Collection<int, array<string, mixed>>
     */
    public function calendarDays(Collection $routes, Collection $conductors, CarbonInterface $weekStart): Collection
    {
        $days = $this->weekDays($weekStart);
        $fallbackDrivers = $this->fallbackDriversForRoutes($routes);
        $assignments = collect();

        if ($this->scheduleAssignmentsAvailable()) {
            try {
                $assignments = RouteScheduleAssignment::query()
                    ->with(['driver', 'assignedBy'])
                    ->whereBetween('assignment_date', [
                        $days->first()?->toDateString(),
                        $days->last()?->toDateString(),
                    ])
                    ->get()
                    ->groupBy(fn (RouteScheduleAssignment $assignment): string => $assignment->assignment_date->toDateString());
            } catch (\Throwable) {
                $assignments = collect();
            }
        }

        return $days->map(function (Carbon $day) use ($assignments, $routes, $conductors, $fallbackDrivers): array {
            /** @var Collection<int, RouteScheduleAssignment> $dailyAssignments */
            $dailyAssignments = ($assignments[$day->toDateString()] ?? collect())
                ->keyBy(fn (RouteScheduleAssignment $assignment): int => (int) $assignment->route_id);

            $driverRoutes = $conductors->mapWithKeys(function (User $conductor) use ($routes, $dailyAssignments, $fallbackDrivers): array {
                $assignedRoutes = $routes
                    ->filter(fn (ClientRoute $route): bool => $this->effectiveDriverId($route, $dailyAssignments, $fallbackDrivers) === $conductor->id)
                    ->values()
                    ->map(fn (ClientRoute $route): array => $this->calendarCard($route, $dailyAssignments->get($route->id)));

                return [$conductor->id => $assignedRoutes];
            });

            $unassignedRoutes = $routes
                ->filter(fn (ClientRoute $route): bool => $this->effectiveDriverId($route, $dailyAssignments, $fallbackDrivers) === null)
                ->values()
                ->map(fn (ClientRoute $route): array => $this->calendarCard($route, $dailyAssignments->get($route->id)));

            return [
                'date' => $day,
                'unassigned_routes' => $unassignedRoutes,
                'driver_routes' => $driverRoutes,
            ];
        });
    }

    public function scheduleRouteForDate(
        ClientRoute $route,
        CarbonInterface $date,
        ?User $driver,
        User $actor,
    ): void {
        $dateValue = Carbon::parse($date)->toDateString();
        $targetDriverId = $driver?->id;
        $baseDriverId = $this->baseDriverIdForRoute($route);
        $matchesBaseAssignment = $targetDriverId === $baseDriverId;
        $staysUnassigned = $targetDriverId === null && $baseDriverId === null;

        if ($matchesBaseAssignment || $staysUnassigned) {
            RouteScheduleAssignment::query()
                ->where('route_id', $route->id)
                ->whereDate('assignment_date', $dateValue)
                ->delete();

            return;
        }

        RouteScheduleAssignment::query()->updateOrCreate(
            [
                'route_id' => $route->id,
                'assignment_date' => $dateValue,
            ],
            [
                'driver_user_id' => $targetDriverId,
                'assigned_by_user_id' => $actor->id,
            ],
        );
    }

    public function canManageAnyRoute(User $user): bool
    {
        return (bool) ($user->isSuperAdmin() || $user->hasAnyRole(['super_admin', 'admin', 'manager']));
    }

    /**
     * @return Collection<int, RouteScheduleAssignment>
     */
    private function assignmentsForDate(CarbonInterface $date): Collection
    {
        if (! $this->scheduleAssignmentsAvailable()) {
            return collect();
        }

        try {
            return RouteScheduleAssignment::query()
                ->whereDate('assignment_date', Carbon::parse($date)->toDateString())
                ->get()
                ->keyBy(fn (RouteScheduleAssignment $assignment): int => (int) $assignment->route_id);
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * @param  Collection<int, RouteScheduleAssignment>  $dailyAssignments
     * @param  Collection<int, int>  $fallbackDrivers
     */
    private function effectiveDriverId(ClientRoute $route, Collection $dailyAssignments, Collection $fallbackDrivers): ?int
    {
        $assignment = $dailyAssignments->get($route->id);

        if ($assignment instanceof RouteScheduleAssignment) {
            return $assignment->driver_user_id;
        }

        return $route->driver_user_id ?? $fallbackDrivers->get($route->id);
    }

    private function calendarCard(ClientRoute $route, ?RouteScheduleAssignment $assignment): array
    {
        return [
            'route' => $route,
            'is_override' => $assignment instanceof RouteScheduleAssignment,
            'source_label' => $assignment instanceof RouteScheduleAssignment ? 'Programada' : 'Base',
            'assigned_by' => $assignment?->assignedBy?->name,
            'driver' => $assignment?->driver,
        ];
    }

    /**
     * @param  Collection<int, ClientRoute>  $routes
     * @return Collection<int, int>
     */
    private function fallbackDriversForRoutes(Collection $routes): Collection
    {
        if ($routes->isEmpty()) {
            return collect();
        }

        $tenantId = $routes->first()?->tenant_id;

        return User::query()
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereIn('route_id', $routes->pluck('id'))
            ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
            ->pluck('id', 'route_id');
    }

    private function baseDriverIdForRoute(ClientRoute $route): ?int
    {
        return $route->driver_user_id
            ?? User::query()
                ->where('tenant_id', $route->tenant_id)
                ->where('route_id', $route->id)
                ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
                ->value('id');
    }

    private function scheduleAssignmentsAvailable(): bool
    {
        try {
            return Schema::hasTable('route_schedule_assignments');
        } catch (\Throwable) {
            return false;
        }
    }
}
