<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RESTful API Controller for Products
 *
 * Endpoints:
 * - GET    /api/v1/products       - List products
 * - GET    /api/v1/products/{id} - Show product
 * - POST   /api/v1/products       - Create product
 * - PUT    /api/v1/products/{id} - Update product
 * - DELETE /api/v1/products/{id} - Delete product
 */
final class ProductApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('products.view'), 403);

        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        $perPage = min($request->integer('per_page', 25), 100);
        $products = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('products.view'), 403);

        $product = Product::with('stocks')->findOrFail($id);

        return response()->json(['data' => $product]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('products.create'), 403);

        $product = Product::create($request->validated());

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'data' => $product,
        ], 201);
    }

    public function update(ProductRequest $request, int $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('products.edit'), 403);

        $product = Product::findOrFail($id);
        $product->update($request->validated());

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'data' => $product,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('products.delete'), 403);

        $product = Product::findOrFail($id);

        // Check if product has stock
        if ($product->stocks()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'error' => 'No se puede eliminar un producto con stock activo.',
            ], 422);
        }

        $product->update(['is_active' => false]);

        return response()->json([
            'message' => 'Producto desactivado correctamente.',
        ]);
    }

    public function stock(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $warehouseId = $request->input('warehouse_id');

        $query = Stock::with(['product', 'warehouse'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true));

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $perPage = min($request->integer('per_page', 50), 100);
        $stock = $query->paginate($perPage);

        return response()->json([
            'data' => $stock->items(),
            'meta' => [
                'current_page' => $stock->currentPage(),
                'last_page' => $stock->lastPage(),
                'per_page' => $stock->perPage(),
                'total' => $stock->total(),
            ],
        ]);
    }

    public function stockByWarehouse(Request $request, int $warehouseId): JsonResponse
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $stock = Stock::with('product')
            ->where('warehouse_id', $warehouseId)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->get();

        return response()->json(['data' => $stock]);
    }
}
