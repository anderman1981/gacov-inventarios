<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\TenantContext;
use App\Http\Requests\RouteRequest;
use App\Http\Requests\ReassignRouteRequest;
use App\Models\Route as ClientRoute;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class RouteAssignmentController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $tenantId = $this->currentTenantId();

        $conductors = User::query()
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
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

    public function create(): View
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $conductors = User::query()
            ->when($tenantId = $this->currentTenantId(), fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
            ->orderBy('name')
            ->get();

        return view('operations.routes.form', [
            'route' => null,
            'conductors' => $conductors,
        ]);
    }

    public function store(RouteRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $route = DB::transaction(function () use ($request): ClientRoute {
            $route = ClientRoute::query()->create([
                'tenant_id' => $request->user()?->tenant_id,
                'name' => trim((string) $request->string('name')),
                'code' => strtoupper(trim((string) $request->string('code'))),
                'vehicle_plate' => $request->filled('vehicle_plate')
                    ? strtoupper(trim((string) $request->string('vehicle_plate')))
                    : null,
                'driver_user_id' => null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->syncRouteVehicleWarehouse($route);
            $this->syncRouteDriver($route, $request->integer('driver_user_id') ?: null);

            return $route->fresh();
        });

        return redirect()
            ->route('operations.routes.edit', $route)
            ->with('success', 'Ruta creada correctamente.');
    }

    public function edit(string $route): View
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $route = $this->resolveRouteOrFail($route);

        $conductors = User::query()
            ->when($tenantId = $this->currentTenantId(), fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
            ->orderBy('name')
            ->get();

        return view('operations.routes.form', compact('route', 'conductors'));
    }

    public function update(RouteRequest $request, string $route): RedirectResponse
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $route = $this->resolveRouteOrFail($route);

        DB::transaction(function () use ($request, $route): void {
            $route->update([
                'name' => trim((string) $request->string('name')),
                'code' => strtoupper(trim((string) $request->string('code'))),
                'vehicle_plate' => $request->filled('vehicle_plate')
                    ? strtoupper(trim((string) $request->string('vehicle_plate')))
                    : null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->syncRouteVehicleWarehouse($route);
            $this->syncRouteDriver($route, $request->integer('driver_user_id') ?: null);
        });

        return redirect()
            ->route('operations.routes.board')
            ->with('success', 'Ruta actualizada correctamente.');
    }

    public function destroy(string $route): RedirectResponse
    {
        abort_unless(auth()->user()?->can('drivers.assign_routes'), 403);

        $route = $this->resolveRouteOrFail($route);

        DB::transaction(function () use ($route): void {
            User::query()
                ->where('route_id', $route->id)
                ->update(['route_id' => null]);

            Warehouse::query()
                ->where('route_id', $route->id)
                ->where('type', 'vehiculo')
                ->update([
                    'is_active' => false,
                    'responsible_user_id' => null,
                ]);

            $route->update([
                'driver_user_id' => null,
                'is_active' => false,
            ]);
        });

        return redirect()
            ->route('operations.routes.board')
            ->with('success', "La ruta {$route->code} fue quitada y quedó inactiva.");
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
                ->when($tenantId = $this->currentTenantId(), fn ($query) => $query->where('tenant_id', $tenantId))
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
            $driver = User::query()
                ->where('tenant_id', $route->tenant_id)
                ->whereKey($route->driver_user_id)
                ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
                ->first();

            if ($driver instanceof User) {
                return $driver;
            }
        }

        return User::query()
            ->where('tenant_id', $route->tenant_id)
            ->where('route_id', $route->id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
            ->first();
    }

    private function resolveRouteOrFail(string $routeIdentifier): ClientRoute
    {
        return ClientRoute::query()
            ->when(is_numeric($routeIdentifier), fn ($query) => $query->whereKey((int) $routeIdentifier), fn ($query) => $query->where('code', $routeIdentifier))
            ->firstOrFail();
    }

    private function currentTenantId(): ?int
    {
        return $this->tenantContext->getTenantId() ?? auth()->user()?->tenant_id;
    }

    private function syncRouteVehicleWarehouse(ClientRoute $route): void
    {
        Warehouse::query()->updateOrCreate(
            [
                'route_id' => $route->id,
                'type' => 'vehiculo',
            ],
            [
                'tenant_id' => $route->tenant_id,
                'name' => 'Vehículo '.$route->name,
                'code' => 'VH-'.$route->code,
                'is_active' => $route->is_active,
                'responsible_user_id' => $route->driver_user_id,
            ],
        );
    }

    private function syncRouteDriver(ClientRoute $route, ?int $driverId): void
    {
        $currentDriver = $route->driver_user_id !== null
            ? User::query()
                ->where('tenant_id', $route->tenant_id)
                ->whereKey($route->driver_user_id)
                ->first()
            : null;

        if ($currentDriver instanceof User && (int) $currentDriver->route_id === (int) $route->id) {
            $currentDriver->update(['route_id' => null]);
        }

        if ($driverId === null) {
            $route->update(['driver_user_id' => null]);
            $this->syncRouteVehicleWarehouse($route);

            return;
        }

        $targetDriver = User::query()
            ->where('tenant_id', $route->tenant_id)
            ->whereKey($driverId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'conductor'))
            ->firstOrFail();

        $targetCurrentRoute = ClientRoute::query()
            ->where('tenant_id', $route->tenant_id)
            ->where('driver_user_id', $targetDriver->id)
            ->whereKeyNot($route->id)
            ->first();

        if ($targetCurrentRoute instanceof ClientRoute) {
            $targetCurrentRoute->update(['driver_user_id' => null]);
            User::query()
                ->whereKey($targetDriver->id)
                ->update(['route_id' => null]);
        }

        $route->update(['driver_user_id' => $targetDriver->id]);
        $targetDriver->update(['route_id' => $route->id]);

        $this->syncRouteVehicleWarehouse($route);
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
