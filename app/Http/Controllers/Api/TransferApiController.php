<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteTransferRequest;
use App\Http\Requests\StoreTransferRequest;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\SearchHelper;
use Illuminate\Support\Facades\DB;

/**
 * RESTful API Controller for Transfer Orders
 *
 * Endpoints:
 * - GET    /api/v1/transfers                     - List transfers
 * - GET    /api/v1/transfers/{id}               - Show transfer
 * - POST   /api/v1/transfers                    - Create transfer
 * - PUT    /api/v1/transfers/{id}               - Update transfer (notes only)
 * - POST   /api/v1/transfers/{id}/approve        - Approve transfer
 * - POST   /api/v1/transfers/{id}/complete      - Complete transfer
 * - POST   /api/v1/transfers/{id}/cancel        - Cancel transfer
 */
final class TransferApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('transfers.view'), 403);

        $query = TransferOrder::with(['originWarehouse', 'destinationWarehouse']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('code', 'like', "%" . SearchHelper::escapeLike($search) . "%");
        }

        $perPage = min($request->integer('per_page', 15), 50);
        $transfers = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('transfers.view'), 403);

        $transfer = TransferOrder::with([
            'originWarehouse',
            'destinationWarehouse',
            'requestedBy',
            'approvedBy',
            'completedBy',
            'items.product',
        ])->findOrFail($id);

        return response()->json(['data' => $transfer]);
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('transfers.create'), 403);

        $validated = $request->validated();

        $transfer = DB::transaction(function () use ($validated): TransferOrder {
            $transfer = TransferOrder::create([
                'origin_warehouse_id' => $validated['origin_warehouse_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'requested_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
                'status' => 'pendiente',
            ]);

            foreach ($validated['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            return $transfer;
        });

        $transfer->load(['originWarehouse', 'destinationWarehouse', 'items.product']);

        return response()->json([
            'message' => 'Traslado creado correctamente.',
            'data' => $transfer,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('transfers.create'), 403);

        $transfer = TransferOrder::findOrFail($id);

        if ($transfer->status !== 'pendiente') {
            return response()->json([
                'error' => 'Solo se pueden editar traslados en estado pendiente.',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $transfer->update($validated);

        return response()->json([
            'message' => 'Traslado actualizado correctamente.',
            'data' => $transfer,
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('transfers.approve'), 403);

        $transfer = TransferOrder::findOrFail($id);

        if ($transfer->status !== 'pendiente') {
            return response()->json([
                'error' => 'Solo se pueden aprobar traslados en estado pendiente.',
            ], 422);
        }

        $transfer->update([
            'status' => 'aprobado',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => "Traslado {$transfer->code} aprobado.",
            'data' => $transfer,
        ]);
    }

    public function complete(CompleteTransferRequest $request, int $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('transfers.complete'), 403);

        $transfer = TransferOrder::with('items')->findOrFail($id);

        if ($transfer->status !== 'aprobado') {
            return response()->json([
                'error' => 'Solo se pueden completar traslados en estado aprobado.',
            ], 422);
        }

        DB::transaction(function () use ($transfer): void {
            foreach ($transfer->items as $item) {
                $qty = $item->quantity_dispatched ?? $item->quantity_requested;

                // Decrease origin warehouse stock
                Stock::where('warehouse_id', $transfer->origin_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('quantity', $qty);

                // Increase destination warehouse stock
                $destStock = Stock::firstOrCreate(
                    ['warehouse_id' => $transfer->destination_warehouse_id, 'product_id' => $item->product_id],
                    ['quantity' => 0]
                );
                $destStock->increment('quantity', $qty);

                // Log movements
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'origin_warehouse_id' => $transfer->origin_warehouse_id,
                    'destination_warehouse_id' => $transfer->destination_warehouse_id,
                    'movement_type' => 'traslado_entrada',
                    'quantity' => $qty,
                    'reference_code' => $transfer->code,
                    'performed_by' => auth()->id(),
                ]);
            }

            $transfer->update([
                'status' => 'completado',
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);
        });

        return response()->json([
            'message' => "Traslado {$transfer->code} completado.",
            'data' => $transfer->fresh(),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        abort_unless(auth()->user()?->can('transfers.create'), 403);

        $transfer = TransferOrder::findOrFail($id);

        if ($transfer->status !== 'pendiente') {
            return response()->json([
                'error' => 'Solo se pueden cancelar traslados en estado pendiente.',
            ], 422);
        }

        $transfer->update(['status' => 'cancelado']);

        return response()->json([
            'message' => "Traslado {$transfer->code} cancelado.",
            'data' => $transfer,
        ]);
    }
}
