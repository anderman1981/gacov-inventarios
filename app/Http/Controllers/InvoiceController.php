<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\TenantContext;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Controlador para el módulo de facturas formales.
 */
final class InvoiceController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * Lista de facturas con filtros.
     */
    public function index(Request $request): View
    {
        $tenant = $this->tenantContext->getTenant();

        $query = Invoice::query()
            ->forTenant($tenant)
            ->withCount('items', 'payments')
            ->orderByDesc('issue_date');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('full_number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('client_nit', 'like', "%{$search}%");
            });
        }

        $invoices = $query->paginate(15)->withQueryString();

        // Estadísticas
        $stats = [
            'total' => Invoice::forTenant($tenant)->count(),
            'draft' => Invoice::forTenant($tenant)->draft()->count(),
            'issued' => Invoice::forTenant($tenant)->issued()->count(),
            'paid' => Invoice::forTenant($tenant)->paid()->count(),
            'total_amount' => Invoice::forTenant($tenant)->sum('total'),
            'pending_amount' => Invoice::forTenant($tenant)->pending()->sum('balance_due'),
        ];

        return view('invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Formulario de creación.
     */
    public function create(): View
    {
        $tenant = $this->tenantContext->getTenant();
        $billingProfile = $tenant->billingProfile;

        // Datos del emisor (empresa GACOV)
        $issuer = [
            'name' => 'Inversiones GACOV S.A.S.',
            'nit' => '901234567-1',
            'address' => 'Colombia',
            'phone' => '',
            'email' => 'info@gacov.com.co',
        ];

        // Próximo número de factura
        $nextNumber = Invoice::generateNumber($tenant);

        // Períodos de facturación
        $billingPeriods = ['mensual', 'trimestral', 'semestral', 'anual'];

        return view('invoices.create', compact('issuer', 'nextNumber', 'billingPeriods'));
    }

    /**
     * Guardar factura nueva.
     */
    public function store(InvoiceStoreRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        $user = $request->user();

        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Calcular número de factura
            $year = date('Y');
            $prefix = $data['prefix'] ?? 'INV';
            $lastNumber = Invoice::where('tenant_id', $tenant->id)
                ->where('prefix', $prefix)
                ->where('full_number', 'like', "{$prefix}-{$year}-%")
                ->max('number') ?? 0;

            $fullNumber = $prefix.'-'.$year.'-'.str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);

            // Crear factura
            $invoice = Invoice::create([
                'prefix' => $prefix,
                'number' => $lastNumber + 1,
                'full_number' => $fullNumber,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => $data['status'] ?? Invoice::STATUS_DRAFT,
                'payment_status' => Invoice::PAYMENT_PENDING,
                'tenant_id' => $tenant->id,
                'created_by' => $user->id,
                // Emisor
                'issuer_name' => $data['issuer_name'],
                'issuer_nit' => $data['issuer_nit'],
                'issuer_address' => $data['issuer_address'] ?? null,
                'issuer_phone' => $data['issuer_phone'] ?? null,
                'issuer_email' => $data['issuer_email'] ?? null,
                // Cliente
                'client_name' => $data['client_name'],
                'client_nit' => $data['client_nit'],
                'client_address' => $data['client_address'] ?? null,
                'client_email' => $data['client_email'] ?? null,
                'client_phone' => $data['client_phone'] ?? null,
                // Notas
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                // Totales iniciales
                'subtotal' => 0,
                'tax_rate' => $data['tax_rate'] ?? 19,
                'tax_amount' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total' => 0,
                'paid_amount' => 0,
                'balance_due' => 0,
            ]);

            // Agregar items
            $items = $data['items'] ?? [];
            foreach ($items as $index => $itemData) {
                $item = $invoice->addItem([
                    'description' => $itemData['description'],
                    'product_key' => $itemData['product_key'] ?? null,
                    'unit' => $itemData['unit'] ?? 'UN',
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_rate' => $itemData['discount_rate'] ?? 0,
                    'tax_rate' => $itemData['tax_rate'] ?? $invoice->tax_rate,
                    'billing_period' => $itemData['billing_period'] ?? null,
                    'module_key' => $itemData['module_key'] ?? null,
                    'plan_name' => $itemData['plan_name'] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }

            // Recalcular totales finales
            $invoice->calculateTotals();
            $invoice->save();

            DB::commit();

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Factura {$fullNumber} creada exitosamente.");

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al crear la factura: '.$e->getMessage());
        }
    }

    /**
     * Ver detalle de factura.
     */
    public function show(Invoice $invoice): View
    {
        $invoice->load(['items', 'payments', 'creator']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Formulario de edición (solo borradores).
     */
    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Solo se pueden editar facturas en estado borrador.');
        }

        $billingPeriods = ['mensual', 'trimestral', 'semestral', 'anual'];

        return view('invoices.edit', compact('invoice', 'billingPeriods'));
    }

    /**
     * Actualizar factura (solo borradores).
     */
    public function update(InvoiceStoreRequest $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Solo se pueden editar facturas en estado borrador.');
        }

        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Actualizar datos principales
            $invoice->update([
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'issuer_name' => $data['issuer_name'],
                'issuer_nit' => $data['issuer_nit'],
                'issuer_address' => $data['issuer_address'] ?? null,
                'issuer_phone' => $data['issuer_phone'] ?? null,
                'issuer_email' => $data['issuer_email'] ?? null,
                'client_name' => $data['client_name'],
                'client_nit' => $data['client_nit'],
                'client_address' => $data['client_address'] ?? null,
                'client_email' => $data['client_email'] ?? null,
                'client_phone' => $data['client_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 19,
                'discount_amount' => $data['discount_amount'] ?? 0,
            ]);

            // Eliminar items existentes y recrear
            $invoice->items()->delete();

            $items = $data['items'] ?? [];
            foreach ($items as $index => $itemData) {
                $invoice->addItem([
                    'description' => $itemData['description'],
                    'product_key' => $itemData['product_key'] ?? null,
                    'unit' => $itemData['unit'] ?? 'UN',
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_rate' => $itemData['discount_rate'] ?? 0,
                    'tax_rate' => $itemData['tax_rate'] ?? $invoice->tax_rate,
                    'billing_period' => $itemData['billing_period'] ?? null,
                    'module_key' => $itemData['module_key'] ?? null,
                    'plan_name' => $itemData['plan_name'] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }

            // Recalcular totales
            $invoice->calculateTotals();
            $invoice->save();

            DB::commit();

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Factura actualizada exitosamente.');

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la factura: '.$e->getMessage());
        }
    }

    /**
     * Emitir factura (cambiar de draft a issued).
     */
    public function issue(Invoice $invoice): RedirectResponse
    {
        try {
            $invoice->issue();

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Factura {$invoice->full_number} emitida exitosamente.");

        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancelar factura.
     */
    public function cancel(Invoice $invoice): RedirectResponse
    {
        try {
            $invoice->cancel();

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Factura {$invoice->full_number} cancelada.");

        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Registrar pago parcial o total.
     */
    public function registerPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoice->balance_due],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $invoice->registerPayment(
                (float) $validated['amount'],
                $validated['payment_method'],
                $validated['reference'] ?? null
            );

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Pago registrado exitosamente.');

        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Generar y descargar PDF.
     */
    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['items', 'tenant', 'creator']);

        // Generar PDF con DomPDF
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ]);

        // Configurar PDF
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'dejavu sans',
        ]);

        // Descargar PDF
        return $pdf->download("factura-{$invoice->full_number}.pdf");
    }

    /**
     * API: Lista de facturas.
     */
    public function apiIndex(Request $request): AnonymousResourceCollection
    {
        $tenant = $this->tenantContext->getTenant();

        $invoices = Invoice::forTenant($tenant)
            ->withCount('items')
            ->orderByDesc('issue_date')
            ->paginate($request->input('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    /**
     * API: Ver factura.
     */
    public function apiShow(Invoice $invoice): InvoiceResource
    {
        $invoice->load(['items', 'payments', 'creator']);

        return new InvoiceResource($invoice);
    }
}
