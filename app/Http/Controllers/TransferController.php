<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CompleteTransferRequest;
use App\Http\Requests\StoreTransferRequest;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\TransferOrderItem;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\SearchHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class TransferController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('transfers.view'), 403);

        $query = TransferOrder::with(['originWarehouse', 'destinationWarehouse', 'requestedBy'])
            ->orderBy('created_at', 'desc');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('code', 'like', "%" . SearchHelper::escapeLike($search) . "%");
        }

        $transfers = $query->paginate(15)->withQueryString();

        $statusOptions = [
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobada',
            'completado' => 'Completada',
            'cancelado' => 'Cancelada',
            'borrador' => 'Borrador',
        ];

        return view('transfers.index', compact('transfers', 'statusOptions'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('transfers.create'), 403);

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        // Cargar stock de todas las bodegas activas para mostrar disponibilidad
        $allStocks = Stock::whereIn('warehouse_id', $warehouses->pluck('id'))
            ->get()
            ->groupBy('warehouse_id')
            ->map(fn ($items) => $items->keyBy('product_id'));

        return view('transfers.create', compact('warehouses', 'products', 'allStocks'));
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('transfers.create'), 403);

        $items = collect($request->input('items', []))
            ->filter(fn ($item) => (int) ($item['quantity_requested'] ?? 0) > 0);

        if ($items->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['items' => 'Debes ingresar al menos un producto con cantidad mayor a cero.']);
        }

        $code = 'TRAS-'.now()->format('Ymd').'-'.str_pad(
            (string) (TransferOrder::whereDate('created_at', today())->count() + 1),
            4,
            '0',
            STR_PAD_LEFT
        );

        DB::transaction(function () use ($request, $items, $code): void {
            $transfer = TransferOrder::create([
                'code' => $code,
                'origin_warehouse_id' => $request->integer('origin_warehouse_id'),
                'destination_warehouse_id' => $request->integer('destination_warehouse_id'),
                'status' => 'pendiente',
                'requested_by' => auth()->id(),
                'notes' => $request->input('notes'),
            ]);

            foreach ($items as $item) {
                TransferOrderItem::create([
                    'transfer_order_id' => $transfer->id,
                    'product_id' => (int) $item['product_id'],
                    'quantity_requested' => (int) $item['quantity_requested'],
                ]);
            }
        });

        return redirect()->route('transfers.index')
            ->with('success', "Orden de traslado {$code} creada exitosamente.");
    }

    public function show(TransferOrder $transfer): View
    {
        abort_unless(auth()->user()?->can('transfers.view'), 403);

        $transfer->load([
            'originWarehouse',
            'destinationWarehouse',
            'requestedBy',
            'approvedBy',
            'completedBy',
            'items.product',
        ]);

        return view('transfers.show', compact('transfer'));
    }

    public function approve(TransferOrder $transfer): RedirectResponse
    {
        abort_unless(auth()->user()?->can('transfers.approve'), 403);

        if ($transfer->status !== 'pendiente') {
            return back()->withErrors(['status' => 'Solo se pueden aprobar traslados en estado pendiente.']);
        }

        $transfer->update([
            'status' => 'aprobado',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Traslado {$transfer->code} aprobado exitosamente.");
    }

    public function complete(CompleteTransferRequest $request, TransferOrder $transfer): RedirectResponse
    {
        abort_unless(auth()->user()?->can('transfers.complete'), 403);

        if ($transfer->status !== 'aprobado') {
            return back()->withErrors(['status' => 'Solo se pueden completar traslados en estado aprobado.']);
        }

        $transfer->load('items.product');

        $receivedItems = collect($request->input('items', []));

        DB::transaction(function () use ($transfer, $receivedItems): void {
            foreach ($transfer->items as $item) {
                $received = (int) ($receivedItems->get($item->id)['quantity_received'] ?? 0);
                $dispatched = $item->quantity_requested;

                // Decrementar stock en bodega origen
                $originStock = Stock::firstOrNew([
                    'warehouse_id' => $transfer->origin_warehouse_id,
                    'product_id' => $item->product_id,
                ]);
                $originStock->quantity = max(0, ($originStock->quantity ?? 0) - $dispatched);
                $originStock->save();

                // Incrementar stock en bodega destino
                $destStock = Stock::firstOrNew([
                    'warehouse_id' => $transfer->destination_warehouse_id,
                    'product_id' => $item->product_id,
                ]);
                $destStock->quantity = ($destStock->quantity ?? 0) + $received;
                $destStock->save();

                // Movimiento de salida en bodega origen
                StockMovement::create([
                    'movement_type' => 'traslado_salida',
                    'origin_warehouse_id' => $transfer->origin_warehouse_id,
                    'destination_warehouse_id' => $transfer->destination_warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $dispatched,
                    'reference_code' => $transfer->code,
                    'notes' => "Traslado {$transfer->code} — despacho",
                    'performed_by' => auth()->id(),
                    'created_at' => now(),
                ]);

                // Movimiento de entrada en bodega destino
                StockMovement::create([
                    'movement_type' => 'traslado_entrada',
                    'origin_warehouse_id' => $transfer->origin_warehouse_id,
                    'destination_warehouse_id' => $transfer->destination_warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $received,
                    'reference_code' => $transfer->code,
                    'notes' => "Traslado {$transfer->code} — recepción",
                    'performed_by' => auth()->id(),
                    'created_at' => now(),
                ]);

                // Actualizar cantidades en el item
                $item->update([
                    'quantity_dispatched' => $dispatched,
                    'quantity_received' => $received,
                ]);
            }

            $transfer->update([
                'status' => 'completado',
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Traslado {$transfer->code} completado exitosamente.");
    }

    public function cancel(TransferOrder $transfer): RedirectResponse
    {
        abort_unless(auth()->user()?->can('transfers.create'), 403);

        if ($transfer->status !== 'pendiente') {
            return back()->withErrors(['status' => 'Solo se pueden cancelar traslados en estado pendiente.']);
        }

        $transfer->update(['status' => 'cancelado']);

        return redirect()->route('transfers.index')
            ->with('success', "Traslado {$transfer->code} cancelado.");
    }
}
