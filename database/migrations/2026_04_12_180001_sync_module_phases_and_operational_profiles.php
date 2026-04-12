<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $modulePhaseMap = [
            'auth' => 1,
            'dashboard' => 1,
            'drivers' => 1,
            'inventory' => 1,
            'products' => 1,
            'machines' => 1,
            'transfers' => 1,
            'users' => 1,
            'ocr' => 1,
            'routes' => 2,
            'sales' => 2,
            'reports' => 2,
            'analytics' => 3,
            'alerts' => 3,
            'world_office' => 4,
            'geolocation' => 4,
            'api' => 4,
            'white_label' => 5,
        ];

        foreach ($modulePhaseMap as $moduleKey => $phaseRequired) {
            DB::table('modules')
                ->where('key', $moduleKey)
                ->update([
                    'phase_required' => $phaseRequired,
                    'updated_at' => now(),
                ]);
        }

        $phaseStartedAt = Carbon::now()->startOfDay();
        $phaseCommitmentEndsAt = $phaseStartedAt->copy()->addMonthsNoOverflow(3);

        $tenantIdsWithoutProfile = DB::table('tenants')
            ->leftJoin('tenant_billing_profiles', 'tenant_billing_profiles.tenant_id', '=', 'tenants.id')
            ->whereNull('tenant_billing_profiles.tenant_id')
            ->pluck('tenants.id');

        foreach ($tenantIdsWithoutProfile as $tenantId) {
            DB::table('tenant_billing_profiles')->insert([
                'tenant_id' => $tenantId,
                'current_phase' => 1,
                'current_phase_value' => 3800000,
                'total_project_value' => 18144000,
                'minimum_commitment_months' => 3,
                'phase_started_at' => $phaseStartedAt,
                'phase_commitment_ends_at' => $phaseCommitmentEndsAt,
                'review_notice_at' => $phaseCommitmentEndsAt->copy()->subDays(15),
                'proposal_reference' => null,
                'notes' => 'Perfil operativo inicial generado automáticamente para activación escalonada por cliente.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $moduleKeys = [
            'auth',
            'dashboard',
            'drivers',
            'inventory',
            'products',
            'machines',
            'transfers',
            'users',
            'ocr',
            'routes',
            'sales',
            'reports',
            'analytics',
            'alerts',
            'world_office',
            'geolocation',
            'api',
            'white_label',
        ];

        DB::table('modules')
            ->whereIn('key', $moduleKeys)
            ->update([
                'phase_required' => 1,
                'updated_at' => now(),
            ]);

        DB::table('tenant_billing_profiles')
            ->where('notes', 'Perfil operativo inicial generado automáticamente para activación escalonada por cliente.')
            ->delete();
    }
};
