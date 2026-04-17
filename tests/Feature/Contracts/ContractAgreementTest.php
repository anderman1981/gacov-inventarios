<?php

declare(strict_types=1);

namespace Tests\Feature\Contracts;

use App\Mail\ContractSignedCopyMail;
use App\Mail\ContractSigningLinkMail;
use App\Models\ContractAgreement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ContractAgreementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_contract_and_send_signing_link(): void
    {
        Mail::fake();

        $tenant = $this->createTenant();
        $admin = $this->createAdmin($tenant);

        $response = $this->actingAs($admin)->post(route('contracts.store'), $this->contractPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('contract_agreements', [
            'tenant_id' => $tenant->id,
            'status' => ContractAgreement::STATUS_PENDING_SIGNATURE,
            'client_company_name' => 'Inversiones GACOV S.A.S.',
            'bank_account_number' => '#1031 266 9607',
        ]);

        Mail::assertSent(ContractSigningLinkMail::class, 1);
    }

    public function test_manager_can_access_contracts_index_during_testing(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createUserWithRole('manager', $tenant);

        $response = $this->actingAs($manager)->get(route('contracts.index'));

        $response->assertOk();
        $response->assertSee('Contratos interactivos');
    }

    public function test_super_admin_can_access_contracts_index_without_tenant_context(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs(User::factory()->create([
            'tenant_id' => null,
            'is_active' => true,
            'is_super_admin' => true,
        ]))->get(route('contracts.index'))
            ->assertOk()
            ->assertSee('Contratos interactivos');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
        ]);
    }

    public function test_contracts_index_shows_quick_access_to_latest_contract(): void
    {
        Mail::fake();

        $tenant = $this->createTenant();
        $admin = $this->createAdmin($tenant);

        $this->actingAs($admin)->post(route('contracts.store'), $this->contractPayload())->assertRedirect();

        $contract = ContractAgreement::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $response = $this->actingAs($admin)->get(route('contracts.index'));

        $response->assertOk();
        $response->assertSee('Abrir último contrato');
        $response->assertSee($contract->contract_number);
        $response->assertSee(route('contracts.show', $contract));
    }

    public function test_admin_can_open_contract_detail_by_direct_id_url(): void
    {
        Mail::fake();

        $tenant = $this->createTenant();
        $admin = $this->createAdmin($tenant);

        $this->actingAs($admin)->post(route('contracts.store'), $this->contractPayload())->assertRedirect();

        $contract = ContractAgreement::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $response = $this->actingAs($admin)->get('/contracts/'.$contract->id);

        $response->assertOk();
        $response->assertSee($contract->contract_number);
        $response->assertSee('Contrato Base de Desarrollo, Implementación y Soporte Temporal');
    }

    public function test_client_can_open_signed_contract_link(): void
    {
        Mail::fake();

        $tenant = $this->createTenant();
        $admin = $this->createAdmin($tenant);

        $this->actingAs($admin)->post(route('contracts.store'), $this->contractPayload())->assertRedirect();

        $contract = ContractAgreement::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $signUrl = URL::temporarySignedRoute(
            'contracts.public.sign',
            now()->addDay(),
            [
                'tenant' => $tenant->id,
                'contract' => $contract->id,
            ]
        );

        $response = $this->get($signUrl);

        $response->assertOk();
        $response->assertSee($contract->client_company_name);
        $response->assertSee($contract->contract_number);
    }

    public function test_client_can_sign_contract_and_receive_final_copies(): void
    {
        Mail::fake();
        Storage::fake('public');

        $tenant = $this->createTenant();
        $admin = $this->createAdmin($tenant);

        $this->actingAs($admin)->post(route('contracts.store'), $this->contractPayload())->assertRedirect();

        $contract = ContractAgreement::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $signUrl = URL::temporarySignedRoute(
            'contracts.public.sign',
            now()->addDay(),
            [
                'tenant' => $tenant->id,
                'contract' => $contract->id,
            ]
        );

        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO2Zs/8AAAAASUVORK5CYII=';

        $response = $this->post($signUrl, [
            'client_signer_name' => 'Anderson Martinez Restrepo',
            'client_signer_document' => '10312669607',
            'client_signature' => 'data:image/png;base64,'.$pngBase64,
        ]);

        $response->assertRedirect();

        $contract->refresh();

        $this->assertSame(ContractAgreement::STATUS_SIGNED, $contract->status);
        $this->assertNotNull($contract->client_signature_path);
        $this->assertNotNull($contract->pdf_path);
        Storage::disk('public')->assertExists($contract->client_signature_path);
        Storage::disk('public')->assertExists($contract->pdf_path);

        Mail::assertSent(ContractSignedCopyMail::class, 2);
    }

    public function test_client_can_download_public_pdf_after_signature(): void
    {
        Mail::fake();
        Storage::fake('public');

        $tenant = $this->createTenant();
        $admin = $this->createAdmin($tenant);

        $this->actingAs($admin)->post(route('contracts.store'), $this->contractPayload())->assertRedirect();

        $contract = ContractAgreement::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $signUrl = URL::temporarySignedRoute(
            'contracts.public.sign',
            now()->addDay(),
            [
                'tenant' => $tenant->id,
                'contract' => $contract->id,
            ]
        );

        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO2Zs/8AAAAASUVORK5CYII=';

        $this->post($signUrl, [
            'client_signer_name' => 'Anderson Martinez Restrepo',
            'client_signer_document' => '10312669607',
            'client_signature' => 'data:image/png;base64,'.$pngBase64,
        ])->assertRedirect();

        $pdfUrl = URL::temporarySignedRoute(
            'contracts.public.pdf',
            now()->addDay(),
            [
                'tenant' => $tenant->id,
                'contract' => $contract->id,
            ]
        );

        $response = $this->get($pdfUrl);

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Tenant Contratos',
            'slug' => 'tenant-contratos',
            'email' => 'tenant-contratos@example.com',
            'is_active' => true,
        ]);
    }

    private function createAdmin(Tenant $tenant): User
    {
        return $this->createUserWithRole('admin', $tenant);
    }

    private function createUserWithRole(string $role, Tenant $tenant): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function contractPayload(): array
    {
        return [
            'contract_date' => '2026-04-16',
            'provider_name' => 'Anderson Martinez Restrepo',
            'provider_document' => '10312669607',
            'provider_email' => 'andersonmares81@gmail.com',
            'provider_phone' => '+57 316 826 5737',
            'provider_address' => 'Sabaneta, Antioquia',
            'client_company_name' => 'Inversiones GACOV S.A.S.',
            'client_document' => '900.983.146-1',
            'client_legal_representative' => 'Carlos Gacov',
            'client_legal_representative_document' => '11223344',
            'client_email' => 'cliente@gacov.com.co',
            'client_phone' => '+57 300 000 0000',
            'client_address' => 'Medellín, Antioquia',
            'bank_name' => 'Bancolombia',
            'bank_account_type' => 'Cuenta de Ahorros',
            'bank_account_number' => '#1031 266 9607',
            'bank_account_holder' => 'Anderson Martinez Restrepo',
            'summary' => 'Desarrollo e implementación del sistema de inventarios GACOV.',
            'client_notes' => 'Flujo inicial con firma digital y envío de copia.',
        ];
    }
}
