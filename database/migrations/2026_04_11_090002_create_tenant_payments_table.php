<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['development', 'operations', 'extra_hours', 'extraordinary']);
            $table->unsignedTinyInteger('phase')->nullable();
            $table->string('description', 255);
            $table->string('invoice_number', 120)->nullable();
            $table->decimal('amount', 12, 2);
            $table->boolean('counts_toward_project_total')->default(false);
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'paid_at']);
            $table->index(['tenant_id', 'counts_toward_project_total']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
    }
};
