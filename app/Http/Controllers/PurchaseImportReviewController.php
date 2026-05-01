<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseImportBatch;
use Illuminate\View\View;

final class PurchaseImportReviewController extends Controller
{
    public function show(PurchaseImportBatch $purchaseImport): View
    {
        $this->authorizePurchaseImports();

        $purchaseImport->load(['warehouse', 'uploader', 'processor', 'discarder']);
        $selectedErrorTab = request()->string('error_tab')->toString();
        $selectedErrorTab = in_array($selectedErrorTab, ['all', 'missing', 'other'], true)
            ? $selectedErrorTab
            : 'all';
        $selectedRowView = request()->string('row_view')->toString();
        $selectedRowView = in_array($selectedRowView, ['valid', 'all'], true)
            ? $selectedRowView
            : 'valid';

        $allErrorRowsQuery = $purchaseImport->rows()->where('status', 'error');
        $missingCodeErrorQuery = $purchaseImport->rows()
            ->where('status', 'error')
            ->where('error_message', 'like', '%Producto no encontrado%');

        $errorTabCounts = [
            'all' => (clone $allErrorRowsQuery)->count(),
            'missing' => (clone $missingCodeErrorQuery)->count(),
        ];
        $errorTabCounts['other'] = max($errorTabCounts['all'] - $errorTabCounts['missing'], 0);

        $unmatchedRowsQuery = $purchaseImport->rows()->where('status', 'error');

        if ($selectedErrorTab === 'missing') {
            $unmatchedRowsQuery->where('error_message', 'like', '%Producto no encontrado%');
        }

        if ($selectedErrorTab === 'other') {
            $unmatchedRowsQuery->where(function ($query): void {
                $query->whereNull('error_message')
                    ->orWhere('error_message', 'not like', '%Producto no encontrado%');
            });
        }

        $unmatchedRows = $unmatchedRowsQuery
            ->orderBy('row_number')
            ->get();

        $productOptions = Product::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->limit(500)
            ->get(['code', 'worldoffice_code', 'supplier_sku', 'name']);

        $rowsQuery = $purchaseImport->rows()
            ->with('product')
            ->orderBy('row_number');

        if ($selectedRowView === 'valid') {
            $rowsQuery->where('status', 'valida');
        }

        $rows = $rowsQuery
            ->paginate(100)
            ->appends([
                'error_tab' => $selectedErrorTab,
                'row_view' => $selectedRowView,
            ]);

        return view('inventory.purchases.show', [
            'batch' => $purchaseImport,
            'unmatchedRows' => $unmatchedRows,
            'productOptions' => $productOptions,
            'selectedErrorTab' => $selectedErrorTab,
            'errorTabCounts' => $errorTabCounts,
            'selectedRowView' => $selectedRowView,
            'rows' => $rows,
        ]);
    }

    private function authorizePurchaseImports(): void
    {
        abort_unless(auth()->user()?->can('inventory.load_excel'), 403);
    }
}
