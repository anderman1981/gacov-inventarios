<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\SearchHelper;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('products.view'), 403);

        $selectedTab = in_array($request->input('status', 'active'), ['active', 'inactive'], true)
            ? (string) $request->input('status', 'active')
            : 'active';
        $perPage = $this->resolvePerPage($request, 25);
        $perPageOptions = [10, 25, 50, 100];

        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%" . SearchHelper::escapeLike($search) . "%")
                    ->orWhere('code', 'like', "%" . SearchHelper::escapeLike($search) . "%")
                    ->orWhere('worldoffice_code', 'like', "%" . SearchHelper::escapeLike($search) . "%")
                    ->orWhere('id', 'like', "%" . SearchHelper::escapeLike($search) . "%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $activeCount = (clone $query)
            ->where('is_active', true)
            ->count();

        $inactiveCount = (clone $query)
            ->where('is_active', false)
            ->count();

        $products = (clone $query)
            ->where('is_active', $selectedTab === 'active')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $categories = [
            'snack' => 'Snacks',
            'bebida_fria' => 'Bebidas frías',
            'bebida_caliente' => 'Bebidas calientes',
            'insumo' => 'Insumos',
            'otro' => 'Otros',
        ];

        return view('products.index', [
            'products' => $products,
            'selectedTab' => $selectedTab,
            'categories' => $categories,
            'totalProducts' => $activeCount + $inactiveCount,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('products.create'), 403);

        return view('products.create');
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('products.create'), 403);
        Product::create($request->validated());

        return redirect()->route('products.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product): View
    {
        abort_unless(auth()->user()?->can('products.edit'), 403);

        return view('products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        abort_unless(auth()->user()?->can('products.edit'), 403);
        $product->update($request->validated());

        return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless(auth()->user()?->can('products.delete'), 403);

        $hasStock = Stock::where('product_id', $product->id)->where('quantity', '>', 0)->exists();
        if ($hasStock) {
            return back()->with('error', 'No se puede desactivar un producto con stock activo en alguna bodega.');
        }

        $product->update(['is_active' => false]);

        return redirect()->route('products.index')->with('success', 'Producto desactivado correctamente.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        abort_unless(auth()->user()?->can('products.edit'), 403);
        $product->update(['is_active' => ! $product->is_active]);
        $estado = $product->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Producto {$estado} correctamente.");
    }

    private function resolvePerPage(Request $request, int $default = 25): int
    {
        $value = $request->integer('per_page', $default);

        if (! in_array($value, [10, 25, 50, 100], true)) {
            return $default;
        }

        return $value;
    }
}
