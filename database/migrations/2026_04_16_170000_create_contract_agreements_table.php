<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_agreements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('contract_number', 60);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->enum('status', ['draft', 'pending_signature', 'signed'])->default('draft');
            $table->date('contract_date');

            $table->string('provider_name', 200);
            $table->string('provider_document', 50)->nullable();
            $table->string('provider_email', 150)->nullable();
            $table->string('provider_phone', 50)->nullable();
            $table->string('provider_address', 255)->nullable();

            $table->string('client_company_name', 200);
            $table->string('client_document', 50);
            $table->string('client_legal_representative', 200);
            $table->string('client_legal_representative_document', 50)->nullable();
            $table->string('client_email', 150);
            $table->string('client_phone', 50)->nullable();
            $table->string('client_address', 255)->nullable();

            $table->string('bank_name', 120);
            $table->string('bank_account_type', 60);
            $table->string('bank_account_number', 60);
            $table->string('bank_account_holder', 200);

            $table->text('summary')->nullable();
            $table->text('client_notes')->nullable();
            $table->string('client_signer_name', 200)->nullable();
            $table->string('client_signer_document', 50)->nullable();
            $table->string('client_signature_path', 500)->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->string('client_signed_ip', 45)->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->timestamp('signature_link_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'contract_number']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_agreements');
    }
};
