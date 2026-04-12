<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('nit', 20)->nullable();
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->char('primary_color', 7)->default('#00D4FF');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('trial_ends_at')->nullable();
            $table->timestamps();
            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
