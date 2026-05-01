<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCase\Inventory\NotifyPurchaseImportIssuesHandler;
use App\Application\UseCase\Inventory\RevalidatePurchaseImportBatchHandler;
use App\Models\PurchaseImportBatch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

final class PurchaseImportBatchActionsController extends Controller
{
    public function notify(
        PurchaseImportBatch $purchaseImport,
        NotifyPurchaseImportIssuesHandler $handler,
    ): RedirectResponse {
        $this->authorizePurchaseImports();
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        try {
            $recipients = $handler->handle($purchaseImport, $actor);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($recipients === 0) {
            return back()->with('error', 'No hay destinatarios disponibles para notificar.');
        }

        return back()->with('success', "Se enviaron notificaciones de revisión a {$recipients} usuario(s) administrador(es).");
    }

    public function verify(
        PurchaseImportBatch $purchaseImport,
        RevalidatePurchaseImportBatchHandler $handler,
    ): RedirectResponse {
        $this->authorizePurchaseImports();

        try {
            $batch = $handler->handle($purchaseImport);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = (int) $batch->error_rows > 0
            ? "Verificación completada. Aún hay {$batch->error_rows} fila(s) por corregir."
            : 'Verificación completada. Todas las filas están listas para confirmar.';

        return redirect()
            ->route('inventory.purchases.show', $batch)
            ->with((int) $batch->error_rows > 0 ? 'error' : 'success', $message);
    }

    public function validateBatch(
        PurchaseImportBatch $purchaseImport,
        RevalidatePurchaseImportBatchHandler $handler,
    ): RedirectResponse {
        $this->authorizePurchaseImports();

        try {
            $batch = $handler->handle($purchaseImport);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ((int) $batch->error_rows > 0) {
            return redirect()
                ->route('inventory.purchases.show', $batch)
                ->with('error', "Validación fallida: hay {$batch->error_rows} fila(s) con errores.");
        }

        return redirect()
            ->route('inventory.purchases.show', $batch)
            ->with('success', 'Validación exitosa. Puedes confirmar y cargar la compra.');
    }

    private function authorizePurchaseImports(): void
    {
        abort_unless(auth()->user()?->can('inventory.load_excel'), 403);
    }
}
