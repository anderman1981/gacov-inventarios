<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * RESTful API Controller for Dashboard Stats
 *
 * Endpoints:
 * - GET /api/v1/dashboard/stats - Get dashboard statistics
 */
final class DashboardApiController extends Controller
{
    public function stats(): JsonResponse
    {
        abort_unless(auth()->user()?->can('dashboard.full'), 403);

        $user = auth()->user();

        // Get main warehouse
        $mainWarehouse = Warehouse::where('type', 'bodega')->first();

        // Total products (active)
        $totalProducts = Product::where('is_active', true)->count();

        // Total stock units
        $totalUnits = $mainWarehouse
            ? Stock::where('warehouse_id', $mainWarehouse->id)->sum('quantity')
            : 0;

        // Low stock products
        $lowStockThreshold = 10;
        $lowStockCount = $mainWarehouse
            ? Stock::where('warehouse_id', $mainWarehouse->id)
                ->where('quantity', '<=', $lowStockThreshold)
                ->where('quantity', '>', 0)
                ->count()
            : 0;

        // Pending transfers
        $pendingTransfers = TransferOrder::where('status', 'pendiente')->count();

        // Recent movements (last 7 days)
        $recentMovements = StockMovement::where('created_at', '>=', now()->subDays(7))->count();

        // Movements by type (last 30 days)
        $movementsByType = StockMovement::where('created_at', '>=', now()->subDays(30))
            ->select('movement_type', DB::raw('COUNT(*) as count'))
            ->groupBy('movement_type')
            ->pluck('count', 'movement_type')
            ->toArray();

        // Stock by category
        $stockByCategory = Product::where('is_active', true)
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        return response()->json([
            'data' => [
                'total_products' => $totalProducts,
                'total_units' => $totalUnits,
                'low_stock_count' => $lowStockCount,
                'pending_transfers' => $pendingTransfers,
                'recent_movements' => $recentMovements,
                'movements_by_type' => $movementsByType,
                'stock_by_category' => $stockByCategory,
            ],
        ]);
    }
}
