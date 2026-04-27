<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worldoffice_exports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', 200);
            $table->string('file_path', 500);
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('total_rows')->default(0);
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['period_from', 'period_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worldoffice_exports');
    }
};
