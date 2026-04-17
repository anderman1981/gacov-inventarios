<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\DriverCashDelivery;
use App\Models\Route;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Gestión de entregas de efectivo (billetes y monedas) a conductores.
 */
final class DriverCashController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('cash.manage'), 403);

        $routeFilter = $request->integer('route_id') ?: null;
        $driverFilter = $request->integer('driver_id') ?: null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        $deliveries = DriverCashDelivery::with(['route', 'driver', 'deliveredBy'])
            ->when($routeFilter, fn ($q) => $q->where('route_id', $routeFilter))
            ->when($driverFilter, fn ($q) => $q->where('driver_user_id', $driverFilter))
            ->whereBetween('delivery_date', [$dateFrom, $dateTo])
            ->latest('delivery_date')
            ->paginate(20)
            ->withQueryString();

        $routes  = Route::where('is_active', true)->orderBy('name')->get();
        $drivers = User::role('conductor')->where('is_active', true)->orderBy('name')->get();

        return view('cash.index', compact('deliveries', 'routes', 'drivers', 'routeFilter', 'driverFilter', 'dateFrom', 'dateTo'));
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()?->can('cash.manage'), 403);

        $routes  = Route::where('is_active', true)->orderBy('name')->get();
        $drivers = User::role('conductor')->where('is_active', true)->orderBy('name')->get();

        return view('cash.create', compact('routes', 'drivers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('cash.manage'), 403);

        $validated = $request->validate([
            'route_id'          => 'required|exists:routes,id',
            'driver_user_id'    => 'required|exists:users,id',
            'delivery_date'     => 'required|date|before_or_equal:' . now()->toDateString(),
            'bill_100000'       => 'required|integer|min:0|max:9999',
            'bill_50000'        => 'required|integer|min:0|max:9999',
            'bill_20000'        => 'required|integer|min:0|max:9999',
            'bill_10000'        => 'required|integer|min:0|max:9999',
            'bill_5000'         => 'required|integer|min:0|max:9999',
            'bill_2000'         => 'required|integer|min:0|max:9999',
            'bill_1000'         => 'required|integer|min:0|max:9999',
            'coin_1000'         => 'required|integer|min:0|max:9999',
            'coin_500'          => 'required|integer|min:0|max:9999',
            'coin_200'          => 'required|integer|min:0|max:9999',
            'coin_100'          => 'required|integer|min:0|max:9999',
            'coin_50'           => 'required|integer|min:0|max:9999',
            'notes'             => 'nullable|string|max:500',
        ]);

        // Validar que al menos una denominación sea > 0
        $totalUnits = collect($validated)->only(array_merge(
            array_keys(DriverCashDelivery::BILL_DENOMINATIONS),
            array_keys(DriverCashDelivery::COIN_DENOMINATIONS)
        ))->sum();

        if ($totalUnits === 0) {
            return back()->withInput()->with('error', 'Debe ingresar al menos una denominación mayor a cero.');
        }

        try {
            DB::transaction(function () use ($validated): void {
                DriverCashDelivery::create([
                    ...$validated,
                    'delivered_by_user_id' => auth()->id(),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error al guardar en la base de datos: ' . $e->getMessage());
        }

        return redirect()->route('cash.index')
            ->with('success', 'Entrega de efectivo registrada correctamente.');
    }

    public function show(int $cashDeliveryId): View
    {
        abort_unless(
            auth()->user()?->can('cash.manage') || auth()->user()?->can('cash.view'),
            403
        );

        $cashDelivery = DriverCashDelivery::query()
            ->with(['route', 'driver', 'deliveredBy'])
            ->findOrFail($cashDeliveryId);

        return view('cash.show', compact('cashDelivery'));
    }
}
