<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ContractAgreement;
use App\Models\Tenant;
use App\Support\Contracts\ContractAgreementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class ContractAgreementSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $contract = ContractAgreement::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'contract_number' => 'AMR-GACOV-2026-04',
            ],
            [
                'created_by' => null,
                'signed_by' => null,
                'sequence' => 4,
                'status' => ContractAgreement::STATUS_DRAFT,
                'contract_date' => Carbon::parse('2026-04-11')->toDateString(),
                'provider_name' => 'Anderson Martinez Restrepo',
                'provider_document' => '8.105.201',
                'provider_email' => 'andersonmares81@gmail.com',
                'provider_phone' => '+57 316 826 5737',
                'provider_address' => 'Sabaneta, Antioquia',
                'client_company_name' => 'Inversiones GACOV S.A.S.',
                'client_document' => '900.983.146-1',
                'client_legal_representative' => 'Representante legal por definir',
                'client_legal_representative_document' => null,
                'client_email' => 'cliente@gacov.com.co',
                'client_phone' => '+57 300 000 0000',
                'client_address' => 'Medellín, Antioquia',
                'bank_name' => 'Bancolombia',
                'bank_account_type' => 'Cuenta de Ahorros',
                'bank_account_number' => '#1031 266 9607',
                'bank_account_holder' => 'Anderson Martinez Restrepo',
                'summary' => 'Contrato base oficial AMR-GACOV SaaS 2026-04 importado desde la plantilla documental.',
                'client_notes' => 'Fuente: docs/quotes/GACOV/contrato-gacov-saas-2026-04.html',
            ]
        );

        /** @var ContractAgreementService $service */
        $service = app(ContractAgreementService::class);
        $service->generatePdf($contract);
    }
}
