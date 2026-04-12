<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();

            // Polymorphic relation
            $table->string('loggable_type');
            $table->unsignedBigInteger('loggable_id');

            // Action details
            $table->string('action', 50); // created, updated, deleted, approved, etc.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();

            // Audit trail
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->string('user_agent', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Indexes for common queries
            $table->index(['loggable_type', 'loggable_id'], 'idx_activity_loggable');
            $table->index('action', 'idx_activity_action');
            $table->index('user_id', 'idx_activity_user');
            $table->index('created_at', 'idx_activity_created');

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
