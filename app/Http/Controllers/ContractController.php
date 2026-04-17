<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\TenantContext;
use App\Http\Requests\SignContractAgreementRequest;
use App\Http\Requests\StoreContractAgreementRequest;
use App\Models\ContractAgreement;
use App\Models\Tenant;
use App\Support\Contracts\ContractAgreementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ContractController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ContractAgreementService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeInternalAccess($request);

        $tenant = $this->currentTenant();

        $contracts = ContractAgreement::query()
            ->forTenant($tenant)
            ->with(['creator', 'signer'])
            ->orderByDesc('contract_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $latestContract = ContractAgreement::query()
            ->forTenant($tenant)
            ->orderByDesc('contract_date')
            ->orderByDesc('id')
            ->first();

        $stats = [
            'total' => ContractAgreement::forTenant($tenant)->count(),
            'draft' => ContractAgreement::forTenant($tenant)->where('status', ContractAgreement::STATUS_DRAFT)->count(),
            'pending' => ContractAgreement::forTenant($tenant)->pendingSignature()->count(),
            'signed' => ContractAgreement::forTenant($tenant)->signed()->count(),
        ];

        return view('contracts.index', compact('contracts', 'stats', 'latestContract'));
    }

    public function create(Request $request): View
    {
        $this->authorizeInternalAccess($request);

        return view('contracts.create', [
            'defaults' => $this->service->defaultProviderData(),
            'contractDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreContractAgreementRequest $request): RedirectResponse
    {
        $this->authorizeInternalAccess($request);

        $tenant = $this->currentTenant();
        $contract = $this->service->createDraft($tenant, $request->user(), $request->validated());

        try {
            $this->service->sendSigningLink($contract);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('contracts.show', $contract)
                ->with('warning', 'El contrato se guardó, pero no se pudo enviar el correo con el enlace de firma. Puedes reenviarlo desde el detalle.');
        }

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', 'Contrato creado y enlace de firma enviado al cliente.');
    }

    public function show(Request $request, int $contract): View
    {
        $this->authorizeInternalAccess($request);

        $contract = $this->resolveInternalContract($contract);
        $signingUrl = $contract->status !== ContractAgreement::STATUS_SIGNED
            ? $this->service->buildSigningUrl($contract)
            : null;
        $pdfUrl = $this->service->buildPdfUrl($contract);

        return view('contracts.show', compact('contract', 'signingUrl', 'pdfUrl'));
    }

    public function pdf(Request $request, int $contract): Response
    {
        $this->authorizeInternalAccess($request);

        $contract = $this->resolveInternalContract($contract);
        $this->service->generatePdf($contract);

        return Storage::disk('public')->download(
            $contract->pdf_path,
            "contrato-{$contract->contract_number}.pdf"
        );
    }

    public function resendLink(Request $request, int $contract): RedirectResponse
    {
        $this->authorizeInternalAccess($request);

        $contract = $this->resolveInternalContract($contract);

        try {
            $this->service->sendSigningLink($contract);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo reenviar el enlace de firma.');
        }

        return back()->with('success', 'Se reenvió el enlace de firma al cliente.');
    }

    public function sign(Request $request, int $tenant, int $contract): View
    {
        abort_unless($request->hasValidSignature(), 403);

        $contractAgreement = $this->resolvePublicContract($tenant, $contract);
        $isLocked = $contractAgreement->status === ContractAgreement::STATUS_SIGNED;

        return view('contracts.sign', [
            'contract' => $contractAgreement,
            'isLocked' => $isLocked,
            'signingUrl' => $request->fullUrl(),
            'pdfUrl' => $this->service->buildPdfUrl($contractAgreement),
        ]);
    }

    public function publicPdf(Request $request, int $tenant, int $contract): Response
    {
        abort_unless($request->hasValidSignature(), 403);

        $contractAgreement = $this->resolvePublicContract($tenant, $contract);
        $this->service->generatePdf($contractAgreement);

        return Storage::disk('public')->download(
            $contractAgreement->pdf_path,
            "contrato-{$contractAgreement->contract_number}.pdf"
        );
    }

    public function finalize(SignContractAgreementRequest $request, int $tenant, int $contract): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $contractAgreement = $this->resolvePublicContract($tenant, $contract);

        if ($contractAgreement->status === ContractAgreement::STATUS_SIGNED) {
            return redirect()
                ->route('contracts.public.sign', ['tenant' => $tenant, 'contract' => $contract])
                ->with('success', 'Este contrato ya fue firmado y bloqueado.');
        }

        $validated = $request->validated();

        $signaturePath = $this->service->storeSignatureImage($contractAgreement, $validated['client_signature']);

        $contractAgreement->forceFill([
            'client_signer_name' => $validated['client_signer_name'],
            'client_signer_document' => $validated['client_signer_document'],
            'client_signature_path' => $signaturePath,
            'client_signed_at' => now(),
            'client_signed_ip' => $request->ip(),
            'status' => ContractAgreement::STATUS_SIGNED,
        ])->save();

        $this->service->generatePdf($contractAgreement);
        $this->service->sendSignedCopies($contractAgreement);

        return redirect()
            ->route('contracts.public.sign', ['tenant' => $tenant, 'contract' => $contractAgreement->id])
            ->with('success', 'Contrato firmado correctamente. Ya quedó bloqueado y se envió la copia firmada.');
    }

    private function authorizeInternalAccess(Request $request): void
    {
        abort_unless(
            $request->user()?->isSuperAdmin()
                || $request->user()?->hasAnyRole(['admin', 'manager', 'contador']),
            403
        );
    }

    private function currentTenant(): Tenant
    {
        $tenant = $this->tenantContext->getTenant();

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        if (auth()->user()?->isSuperAdmin()) {
            return Tenant::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->firstOrFail();
        }

        abort(403);

        return $tenant;
    }

    private function resolveInternalContract(int $contractId): ContractAgreement
    {
        $contract = ContractAgreement::withoutGlobalScopes()
            ->findOrFail($contractId);

        if (auth()->user()?->isSuperAdmin()) {
            return $contract;
        }

        $tenant = $this->currentTenant();

        abort_unless((int) $contract->tenant_id === (int) $tenant->id, 404);

        return $contract;
    }

    private function resolvePublicContract(int $tenantId, int $contractId): ContractAgreement
    {
        return ContractAgreement::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($contractId);
    }
}
