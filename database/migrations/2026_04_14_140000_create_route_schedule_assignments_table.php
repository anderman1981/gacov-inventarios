<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_schedule_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->date('assignment_date');
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'route_id', 'assignment_date'],
                'uq_route_schedule_assignments_tenant_route_date'
            );
            $table->index(['tenant_id', 'assignment_date'], 'idx_route_schedule_assignments_tenant_date');
            $table->index(['tenant_id', 'driver_user_id', 'assignment_date'], 'idx_route_schedule_assignments_driver_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_schedule_assignments');
    }
};
