<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReassignRouteRequest;
use App\Models\Route as ClientRoute;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class RouteAssignmentController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $conductors = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
            ->with('route')
            ->orderBy('name')
            ->get();

        $routes = ClientRoute::query()
            ->where('is_active', true)
            ->with(['driver'])
            ->withCount('machines')
            ->orderBy('name')
            ->get();

        $conductorIds = $conductors->pluck('id');

        $assignedRoutesByDriver = $routes
            ->filter(fn (ClientRoute $route): bool => $route->driver_user_id !== null && $conductorIds->contains($route->driver_user_id))
            ->keyBy(fn (ClientRoute $route): int => (int) $route->driver_user_id);

        $unassignedRoutes = $routes
            ->filter(fn (ClientRoute $route): bool => $route->driver_user_id === null || ! $conductorIds->contains($route->driver_user_id))
            ->values();

        return view('operations.routes.board', compact(
            'conductors',
            'assignedRoutesByDriver',
            'unassignedRoutes'
        ));
    }

    public function reassign(ReassignRouteRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $route = ClientRoute::query()
            ->whereKey($request->integer('route_id'))
            ->where('is_active', true)
            ->firstOrFail();

        $targetDriver = $request->filled('target_driver_id')
            ? User::query()
                ->whereKey($request->integer('target_driver_id'))
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
                ->firstOrFail()
            : null;

        $sourceDriver = $this->resolveAssignedDriver($route);

        if ($targetDriver !== null && $sourceDriver?->is($targetDriver)) {
            return redirect()
                ->route('operations.routes.board')
                ->with('success', "La ruta {$route->code} ya estaba asignada a {$targetDriver->name}.");
        }

        $targetCurrentRoute = $targetDriver !== null
            ? ClientRoute::query()
                ->where('driver_user_id', $targetDriver->id)
                ->whereKeyNot($route->id)
                ->first()
            : null;

        DB::transaction(function () use ($route, $targetDriver, $sourceDriver, $targetCurrentRoute): void {
            User::query()->where('route_id', $route->id)->update(['route_id' => null]);

            if ($sourceDriver !== null) {
                $sourceDriver->update(['route_id' => null]);
            }

            if ($targetCurrentRoute !== null) {
                User::query()->where('route_id', $targetCurrentRoute->id)->update(['route_id' => null]);
                $targetCurrentRoute->update(['driver_user_id' => null]);
            }

            $route->update([
                'driver_user_id' => $targetDriver?->id,
            ]);

            if ($targetDriver !== null) {
                $targetDriver->update(['route_id' => $route->id]);
            }

            if ($sourceDriver !== null && $targetCurrentRoute !== null) {
                $targetCurrentRoute->update(['driver_user_id' => $sourceDriver->id]);
                $sourceDriver->update(['route_id' => $targetCurrentRoute->id]);
            }
        });

        return redirect()
            ->route('operations.routes.board')
            ->with('success', $this->successMessage($route, $targetDriver, $sourceDriver, $targetCurrentRoute));
    }

    private function resolveAssignedDriver(ClientRoute $route): ?User
    {
        if ($route->driver_user_id !== null) {
            $driver = User::query()->whereKey($route->driver_user_id)->first();

            if ($driver instanceof User) {
                return $driver;
            }
        }

        return User::query()
            ->where('route_id', $route->id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
            ->first();
    }

    private function successMessage(
        ClientRoute $route,
        ?User $targetDriver,
        ?User $sourceDriver,
        ?ClientRoute $targetCurrentRoute,
    ): string {
        if ($targetDriver === null) {
            return "La ruta {$route->code} quedó sin conductor asignado.";
        }

        if ($sourceDriver !== null && $targetCurrentRoute !== null) {
            return "Se intercambiaron {$route->code} y {$targetCurrentRoute->code} entre {$sourceDriver->name} y {$targetDriver->name}.";
        }

        if ($targetCurrentRoute !== null) {
            return "La ruta {$route->code} quedó asignada a {$targetDriver->name} y {$targetCurrentRoute->code} quedó libre para reasignación.";
        }

        $origin = $sourceDriver?->name ?? 'sin conductor previo';

        return "La ruta {$route->code} pasó de {$origin} a {$targetDriver->name}.";
    }
}
