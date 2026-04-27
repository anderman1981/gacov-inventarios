<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_billing_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_phase')->default(1);
            $table->decimal('current_phase_value', 12, 2)->default(3800000);
            $table->decimal('total_project_value', 12, 2)->default(18144000);
            $table->unsignedTinyInteger('minimum_commitment_months')->default(3);
            $table->dateTime('phase_started_at')->nullable();
            $table->dateTime('phase_commitment_ends_at')->nullable();
            $table->dateTime('review_notice_at')->nullable();
            $table->string('proposal_reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('current_phase');
            $table->index('review_notice_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_billing_profiles');
    }
};
