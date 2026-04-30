<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCase\Inventory\ConfirmPurchaseImportHandler;
use App\Application\UseCase\Inventory\StagePurchaseCsvImportHandler;
use App\Http\Requests\StagePurchaseImportRequest;
use App\Models\PurchaseImportBatch;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PurchaseImportController extends Controller
{
    public function index(): View
    {
        $this->authorizePurchaseImports();

        $mainWarehouse = $this->resolveMainWarehouse();
        $batches = PurchaseImportBatch::query()
            ->with(['uploader', 'warehouse'])
            ->latest()
            ->paginate(12);

        return view('inventory.purchases.index', compact('mainWarehouse', 'batches'));
    }

    public function template(): StreamedResponse
    {
        $this->authorizePurchaseImports();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-compras-gacov.csv"',
        ];

        return Response::streamDownload(function (): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'codigo_producto',
                'cantidad',
                'costo_unitario',
                'proveedor',
                'factura',
                'fecha_compra',
                'observaciones',
            ], ';');
            fputcsv($output, [
                'P001',
                '24',
                '1800',
                'Proveedor Ejemplo S.A.S.',
                'FC-1001',
                now()->toDateString(),
                'Compra de reposición',
            ], ';');
        }, 'template-compras-gacov.csv', $headers);
    }

    public function store(
        StagePurchaseImportRequest $request,
        StagePurchaseCsvImportHandler $handler,
    ): RedirectResponse {
        try {
            $batch = $handler->handle(
                file: $request->file('purchase_file'),
                user: $request->user(),
                defaults: [
                    'supplier' => $request->input('supplier'),
                    'invoice_number' => $request->input('invoice_number'),
                    'purchase_date' => $request->input('purchase_date'),
                ],
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = "Compra cargada en tabla temporal. Filas válidas: {$batch->valid_rows}.";

        if ((int) $batch->error_rows > 0) {
            $message .= " Filas con error: {$batch->error_rows}. Corrige el CSV y vuelve a cargar antes de confirmar.";
        }

        return redirect()
            ->route('inventory.purchases.show', $batch)
            ->with((int) $batch->error_rows > 0 ? 'error' : 'success', $message);
    }

    public function show(PurchaseImportBatch $purchaseImport): View
    {
        $this->authorizePurchaseImports();

        $purchaseImport->load(['warehouse', 'uploader', 'processor', 'discarder']);
        $rows = $purchaseImport->rows()
            ->with('product')
            ->orderBy('row_number')
            ->paginate(100);

        return view('inventory.purchases.show', [
            'batch' => $purchaseImport,
            'rows' => $rows,
        ]);
    }

    public function confirm(
        PurchaseImportBatch $purchaseImport,
        ConfirmPurchaseImportHandler $handler,
    ): RedirectResponse {
        $this->authorizePurchaseImports();

        try {
            $batch = $handler->handle($purchaseImport, auth()->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inventory.purchases.show', $batch)
            ->with('success', "Compra confirmada. Se cargaron {$batch->total_units} unidades a bodega.");
    }

    public function destroy(PurchaseImportBatch $purchaseImport): RedirectResponse
    {
        $this->authorizePurchaseImports();

        if ($purchaseImport->status === 'procesado') {
            return back()->with('error', 'Una compra procesada no se puede descartar.');
        }

        $purchaseImport->update([
            'status' => 'descartado',
            'discarded_at' => now(),
            'discarded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('inventory.purchases.index')
            ->with('success', 'Compra temporal descartada. El inventario no fue modificado.');
    }

    private function authorizePurchaseImports(): void
    {
        abort_unless(auth()->user()?->can('inventory.load_excel'), 403);
    }

    private function resolveMainWarehouse(): ?Warehouse
    {
        return Warehouse::query()
            ->where('type', 'bodega')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }
}
