<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('machine_stocking_records', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('machine_id')->constrained('machines');
            $table->foreignId('route_id')->constrained('routes');
            $table->foreignId('vehicle_warehouse_id')->constrained('warehouses');
            $table->foreignId('performed_by')->constrained('users');
            $table->enum('status', ['en_proceso','completado','con_novedad'])->default('en_proceso');
            $table->string('offline_local_id', 60)->nullable()->index();
            $table->boolean('was_offline')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('exported_to_wo_at')->nullable();
            $table->timestamps();
            $table->index(['machine_id','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('machine_stocking_records'); }
};
