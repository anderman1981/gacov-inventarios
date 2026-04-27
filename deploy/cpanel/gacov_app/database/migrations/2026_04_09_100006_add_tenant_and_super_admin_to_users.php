<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->boolean('is_super_admin')->default(false)->after('is_active');
            $table->index('tenant_id');
            $table->index('is_super_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['is_super_admin']);
            $table->dropColumn(['tenant_id', 'is_super_admin']);
        });
    }
};
