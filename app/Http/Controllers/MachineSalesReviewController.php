<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MachineSale;
use App\Models\Route;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Revisión gerencial de ventas de máquinas.
 * Permite al manager filtrar por ruta, ubicación y máquina,
 * y ver el desglose de unidades, costos, precios, efectivo y categorías.
 */
final class MachineSalesReviewController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('sales.review'), 403);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());
        $routeId  = $request->integer('route_id') ?: null;
        $location = $request->input('location', '');
        $machineId = $request->integer('machine_id') ?: null;
        $search   = trim((string) $request->input('search', ''));
        $perPage  = max(5, min(50, $request->integer('per_page', 12)));

        // ── Catálogos para filtros ─────────────────────────────────────
        $routes    = Route::where('is_active', true)->orderBy('name')->get();
        $locations = Machine::when($routeId, fn ($q) => $q->where('route_id', $routeId))
            ->whereNotNull('location')
            ->where('is_active', true)
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        $machines = Machine::where('is_active', true)
            ->when($routeId, fn ($q) => $q->where('route_id', $routeId))
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $like = '%'.$search.'%';

                $q->where(function (Builder $searchQuery) use ($like): void {
                    $searchQuery
                        ->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('worldoffice_code', 'like', $like)
                        ->orWhere('location', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();

        // ── Consulta principal ─────────────────────────────────────────
        $salesQuery = MachineSale::with(['machine.route', 'items.product'])
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $searchQuery) use ($like): void {
                    $searchQuery
                        ->where('code', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereHas('machine', function (Builder $machineQuery) use ($like): void {
                            $machineQuery->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like)
                                ->orWhere('worldoffice_code', 'like', $like)
                                ->orWhere('location', 'like', $like)
                                ->orWhereHas('route', function (Builder $routeQuery) use ($like): void {
                                    $routeQuery->where('name', 'like', $like)
                                        ->orWhere('code', 'like', $like);
                                });
                        })
                        ->orWhereHas('items.product', function (Builder $productQuery) use ($like): void {
                            $productQuery->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        });
                });
            })
            ->whereHas('machine', function (Builder $q) use ($routeId, $location, $machineId): void {
                $q->where('is_active', true);
                if ($routeId) {
                    $q->where('route_id', $routeId);
                }
                if ($location) {
                    $q->where('location', $location);
                }
                if ($machineId) {
                    $q->where('id', $machineId);
                }
            })
            ->latest('sale_date')
            ->latest('id');

        $summarySales = (clone $salesQuery)->get();
        $sales = (clone $salesQuery)->paginate($perPage)->withQueryString();

        // ── Agregaciones ───────────────────────────────────────────────
        $summary = $this->buildSummary($summarySales);

        return view('machines.sales-review', compact(
            'sales', 'summary', 'routes', 'locations', 'machines',
            'dateFrom', 'dateTo', 'routeId', 'location', 'machineId', 'search', 'perPage'
        ));
    }

    /**
     * Construye el resumen agregado por categoría y por máquina.
     *
     * @param  Collection<int, MachineSale>  $sales
     * @return array<string, mixed>
     */
    private function buildSummary(Collection $sales): array
    {
        $categoryLabels = [
            'cafe'         => 'Café',
            'suplemento'   => 'Suplementos',
            'bebida_fria'  => 'Bebida fría',
            'bebida_caliente' => 'Bebida caliente',
            'snack'        => 'Snack',
            'insumo'       => 'Insumo',
            'otro'         => 'Otro',
        ];

        $byCategory   = [];  // category → [units, cost_total, revenue_total]
        $byProduct    = [];  // product_id → [name, category, units, cost_unit, avg_price, revenue]
        $byMachine    = [];  // machine_id → [name, location, route, units, revenue, cash_total]

        $totalUnits   = 0;
        $totalRevenue = 0.0;
        $totalCostBase = 0.0;
        $totalCashBills = 0;
        $totalCashCoins = 0;
        $totalCashAmount = 0;

        foreach ($sales as $sale) {
            $machine = $sale->machine;
            $machineKey = $machine?->id ?? 0;

            if (! isset($byMachine[$machineKey])) {
                $byMachine[$machineKey] = [
                    'id'         => $machine?->id ?? 0,
                    'code'       => $machine?->code ?? '—',
                    'name'       => $machine?->name ?? 'N/A',
                    'location'   => $machine?->location ?? '—',
                    'route'      => $machine?->route?->name ?? '—',
                    'units'      => 0,
                    'revenue'    => 0.0,
                    'cash_bills' => 0,
                    'cash_coins' => 0,
                    'cash_total' => 0,
                ];
            }

            $byMachine[$machineKey]['cash_bills'] += $sale->cash_bills;
            $byMachine[$machineKey]['cash_coins'] += $sale->cash_coins;
            $byMachine[$machineKey]['cash_total'] += $sale->cash_total;
            $totalCashBills  += $sale->cash_bills;
            $totalCashCoins  += $sale->cash_coins;
            $totalCashAmount += $sale->cash_total;

            foreach ($sale->items as $item) {
                $product  = $item->product;
                $category = $product?->category ?? 'otro';
                $prodId   = $product?->id ?? 0;
                $qty      = (int) $item->quantity_sold;
                $salePrice  = (float) $item->unit_price;
                $costPrice  = (float) ($product?->unit_price ?? 0);
                $revenue    = $qty * $salePrice;
                $costTotal  = $qty * $costPrice;

                // Por categoría
                if (! isset($byCategory[$category])) {
                    $byCategory[$category] = ['label' => $categoryLabels[$category] ?? $category, 'units' => 0, 'cost' => 0.0, 'revenue' => 0.0];
                }
                $byCategory[$category]['units']   += $qty;
                $byCategory[$category]['cost']    += $costTotal;
                $byCategory[$category]['revenue'] += $revenue;

                // Por producto
                if (! isset($byProduct[$prodId])) {
                    $byProduct[$prodId] = [
                        'name'      => $product?->name ?? 'N/A',
                        'category'  => $categoryLabels[$category] ?? $category,
                        'units'     => 0,
                        'cost_unit' => $costPrice,
                        'revenue'   => 0.0,
                        'price_sum' => 0.0,   // para calcular precio promedio
                        'count'     => 0,
                    ];
                }
                $byProduct[$prodId]['units']     += $qty;
                $byProduct[$prodId]['revenue']   += $revenue;
                $byProduct[$prodId]['price_sum'] += $salePrice;
                $byProduct[$prodId]['count']     += 1;

                // Por máquina
                $byMachine[$machineKey]['units']   += $qty;
                $byMachine[$machineKey]['revenue'] += $revenue;

                $totalUnits    += $qty;
                $totalRevenue  += $revenue;
                $totalCostBase += $costTotal;
            }
        }

        // Calcular precio promedio real por producto
        foreach ($byProduct as $id => $data) {
            $byProduct[$id]['avg_price'] = $data['count'] > 0
                ? round($data['price_sum'] / $data['count'], 2)
                : 0.0;
        }

        // Ordenar categorías: café y suplementos primero
        $categoryOrder = ['cafe', 'suplemento', 'bebida_fria', 'bebida_caliente', 'snack', 'insumo', 'otro'];
        uksort($byCategory, fn ($a, $b) => array_search($a, $categoryOrder) <=> array_search($b, $categoryOrder));

        // Ordenar productos por unidades desc
        uasort($byProduct, fn ($a, $b) => $b['units'] <=> $a['units']);

        // Ordenar máquinas por revenue desc
        uasort($byMachine, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return [
            'total_sales'    => $sales->count(),
            'total_units'    => $totalUnits,
            'total_revenue'  => $totalRevenue,
            'total_cost'     => $totalCostBase,
            'gross_margin'   => $totalRevenue > 0 ? round((($totalRevenue - $totalCostBase) / $totalRevenue) * 100, 1) : 0.0,
            'cash_bills'     => $totalCashBills,
            'cash_coins'     => $totalCashCoins,
            'cash_total'     => $totalCashAmount,
            'by_category'    => $byCategory,
            'by_product'     => $byProduct,
            'by_machine'     => $byMachine,
        ];
    }
}
