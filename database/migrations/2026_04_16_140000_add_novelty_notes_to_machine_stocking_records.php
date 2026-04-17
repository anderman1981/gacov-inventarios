<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_stocking_records', function (Blueprint $table): void {
            $table->text('novelty_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('machine_stocking_records', function (Blueprint $table): void {
            $table->dropColumn('novelty_notes');
        });
    }
};
