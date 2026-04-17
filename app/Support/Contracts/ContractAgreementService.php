<?php

declare(strict_types=1);

namespace App\Support\Contracts;

use App\Mail\ContractSignedCopyMail;
use App\Mail\ContractSigningLinkMail;
use App\Models\ContractAgreement;
use App\Models\Tenant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

final class ContractAgreementService
{
    public function defaultProviderData(): array
    {
        return [
            'provider_name' => 'Anderson Martinez Restrepo',
            'provider_document' => null,
            'provider_email' => 'andersonmares81@gmail.com',
            'provider_phone' => '+57 316 826 5737',
            'provider_address' => 'Sabaneta, Antioquia',
            'bank_name' => 'Bancolombia',
            'bank_account_type' => 'Cuenta de Ahorros',
            'bank_account_number' => '#1031 266 9607',
            'bank_account_holder' => 'Anderson Martinez Restrepo',
        ];
    }

    public function generateNumber(Tenant $tenant, Carbon $contractDate): string
    {
        $period = $contractDate->format('Y-m');
        $prefix = 'AMR-GACOV-'.$period.'-';

        $lastSequence = ContractAgreement::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('contract_number', 'like', $prefix.'%')
            ->max('sequence');

        $sequence = is_numeric($lastSequence) ? ((int) $lastSequence + 1) : 1;

        return sprintf('%s%03d', $prefix, $sequence);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(Tenant $tenant, User $creator, array $data): ContractAgreement
    {
        $contractDate = Carbon::parse((string) $data['contract_date']);
        $contractNumber = $this->generateNumber($tenant, $contractDate);

        return ContractAgreement::create(array_merge(
            $this->defaultProviderData(),
            [
                'tenant_id' => $tenant->id,
                'created_by' => $creator->id,
                'contract_number' => $contractNumber,
                'sequence' => (int) substr($contractNumber, -3),
                'status' => ContractAgreement::STATUS_DRAFT,
                'contract_date' => $contractDate->toDateString(),
                'client_company_name' => $data['client_company_name'],
                'client_document' => $data['client_document'],
                'client_legal_representative' => $data['client_legal_representative'],
                'client_legal_representative_document' => $data['client_legal_representative_document'] ?? null,
                'client_email' => $data['client_email'],
                'client_phone' => $data['client_phone'] ?? null,
                'client_address' => $data['client_address'] ?? null,
                'summary' => $data['summary'] ?? null,
                'client_notes' => $data['client_notes'] ?? null,
            ],
        ));
    }

    public function buildSigningUrl(ContractAgreement $contract): string
    {
        return URL::temporarySignedRoute(
            'contracts.public.sign',
            now()->addDays(30),
            [
                'tenant' => $contract->tenant_id,
                'contract' => $contract->id,
            ]
        );
    }

    public function buildPdfUrl(ContractAgreement $contract): string
    {
        return URL::temporarySignedRoute(
            'contracts.public.pdf',
            now()->addDays(30),
            [
                'tenant' => $contract->tenant_id,
                'contract' => $contract->id,
            ]
        );
    }

    public function markSignatureLinkSent(ContractAgreement $contract): void
    {
        $contract->forceFill([
            'status' => ContractAgreement::STATUS_PENDING_SIGNATURE,
            'signature_link_sent_at' => now(),
        ])->save();
    }

    public function storeSignatureImage(ContractAgreement $contract, string $dataUri): string
    {
        if (! str_starts_with($dataUri, 'data:image/png;base64,')) {
            throw new RuntimeException('Formato de firma inválido.');
        }

        $base64 = substr($dataUri, strlen('data:image/png;base64,'));
        $binary = base64_decode($base64, true);

        if ($binary === false) {
            throw new RuntimeException('No se pudo decodificar la firma.');
        }

        $path = sprintf(
            'contracts/%d/signatures/%s-client.png',
            $contract->tenant_id,
            Str::slug($contract->contract_number)
        );

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public function generatePdf(ContractAgreement $contract): string
    {
        $contract->loadMissing(['creator', 'signer', 'tenant']);

        $pdf = Pdf::loadView('contracts.pdf', [
            'contract' => $contract,
            'signingUrl' => $this->buildSigningUrl($contract),
            'signatureImageSrc' => $contract->client_signature_path !== null
                ? storage_path('app/public/'.$contract->client_signature_path)
                : null,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
        ]);

        $path = sprintf(
            'contracts/%d/%s.pdf',
            $contract->tenant_id,
            Str::slug($contract->contract_number)
        );

        Storage::disk('public')->put($path, $pdf->output());

        $contract->forceFill([
            'pdf_path' => $path,
        ])->save();

        return $path;
    }

    public function sendSigningLink(ContractAgreement $contract): void
    {
        Mail::to($contract->client_email)->send(
            new ContractSigningLinkMail($contract, $this->buildSigningUrl($contract))
        );

        $this->markSignatureLinkSent($contract);
    }

    public function sendSignedCopies(ContractAgreement $contract): void
    {
        if ($contract->pdf_path === null) {
            throw new RuntimeException('No existe un PDF generado para este contrato.');
        }

        $pdfPath = Storage::disk('public')->path($contract->pdf_path);

        Mail::to($contract->client_email)->send(
            new ContractSignedCopyMail($contract, $pdfPath)
        );

        if ($contract->provider_email !== null) {
            Mail::to($contract->provider_email)->send(
                new ContractSignedCopyMail($contract, $pdfPath)
            );
        }
    }
}
