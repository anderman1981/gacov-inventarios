<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->boolean('must_change_password')->default(true)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->foreignId('route_id')->nullable()->after('last_login_at')->constrained('routes')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropColumn(['phone','is_active','must_change_password','last_login_at','route_id']);
        });
    }
};
