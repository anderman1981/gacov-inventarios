<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // STATIC LOG METHODS
    // ──────────────────────────────────────────────────────────────

    public function test_log_created_stores_action_created(): void
    {
        $product = Product::factory()->create(['name' => 'Coca Cola']);

        $log = ActivityLog::logCreated($product);

        $this->assertSame(ActivityLog::ACTION_CREATED, $log->action);
        $this->assertSame(Product::class, $log->loggable_type);
        $this->assertSame($product->id, $log->loggable_id);
        $this->assertNotNull($log->new_values);
    }

    public function test_log_updated_stores_old_and_new_values(): void
    {
        $product = Product::factory()->create(['name' => 'Pepsi Original']);

        $log = ActivityLog::logUpdated(
            $product,
            ['name' => 'Pepsi Original'],
            ['name' => 'Pepsi Max'],
        );

        $this->assertSame(ActivityLog::ACTION_UPDATED, $log->action);
        $this->assertSame('Pepsi Original', $log->old_values['name']);
        $this->assertSame('Pepsi Max', $log->new_values['name']);
    }

    public function test_log_deleted_stores_model_attributes_as_old_values(): void
    {
        $product = Product::factory()->create(['name' => 'Producto Borrado']);

        $log = ActivityLog::logDeleted($product);

        $this->assertSame(ActivityLog::ACTION_DELETED, $log->action);
        $this->assertNotNull($log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_log_custom_stores_any_action(): void
    {
        $product = Product::factory()->create();

        $log = ActivityLog::logCustom(
            $product,
            ActivityLog::ACTION_APPROVED,
            ['status' => 'pending'],
            ['status' => 'approved'],
            'Aprobación manual',
        );

        $this->assertSame(ActivityLog::ACTION_APPROVED, $log->action);
        $this->assertSame('Aprobación manual', $log->description);
    }

    // ──────────────────────────────────────────────────────────────
    // DESCRIPTION GENERATION
    // ──────────────────────────────────────────────────────────────

    public function test_auto_description_for_created_uses_model_name(): void
    {
        $product = Product::factory()->create(['name' => 'Café Colombiano']);

        $log = ActivityLog::logCreated($product);

        $this->assertStringContainsString('Product', $log->description);
        $this->assertStringContainsString('Café Colombiano', $log->description);
    }

    public function test_custom_description_overrides_auto_description(): void
    {
        $product = Product::factory()->create();

        $log = ActivityLog::logCreated($product, null, 'Mi descripción personalizada');

        $this->assertSame('Mi descripción personalizada', $log->description);
    }

    public function test_description_falls_back_to_code_when_name_is_empty(): void
    {
        // getModelIdentifier prueba: name → code → title → email → id
        // Con name='' cae a 'code', no a 'id'
        $product = Product::factory()->create(['name' => '']);

        $log = ActivityLog::logCreated($product);

        $this->assertNotEmpty($log->description);
        $this->assertStringContainsString($product->code, $log->description);
    }

    public function test_description_falls_back_to_id_when_no_identifier_fields(): void
    {
        // Vaciar name y code para forzar el fallback hasta 'id'
        $product = Product::factory()->create(['name' => '', 'code' => '']);

        $log = ActivityLog::logCreated($product);

        $this->assertNotEmpty($log->description);
        $this->assertStringContainsString((string) $product->id, $log->description);
    }

    // ──────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────

    public function test_scope_for_user_filters_by_user_id(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $other = User::factory()->create(['is_super_admin' => true]);
        $product = Product::factory()->create();

        $this->actingAs($user);
        ActivityLog::logCreated($product);

        $this->actingAs($other);
        ActivityLog::logCreated($product);

        $logs = ActivityLog::forUser($user->id)->get();
        $this->assertSame(1, $logs->count());
    }

    public function test_scope_of_action_filters_by_action(): void
    {
        // El trait LogsActivity auto-genera un 'created' al crear el producto.
        // Solo contamos updated y deleted que NO son generados automáticamente.
        $product = Product::factory()->create();

        ActivityLog::logUpdated($product, ['name' => 'old'], ['name' => 'new']);
        ActivityLog::logDeleted($product);

        $this->assertGreaterThanOrEqual(1, ActivityLog::ofAction(ActivityLog::ACTION_CREATED)->count());
        $this->assertSame(1, ActivityLog::ofAction(ActivityLog::ACTION_UPDATED)->count());
        $this->assertSame(1, ActivityLog::ofAction(ActivityLog::ACTION_DELETED)->count());
    }

    public function test_scope_for_model_filters_by_class(): void
    {
        // Product has LogsActivity trait → factory auto-creates 1 log.
        // User does NOT have LogsActivity, so we log it manually.
        $product = Product::factory()->create();
        $user    = User::factory()->create(['is_super_admin' => true]);

        ActivityLog::logCreated($user);

        $productLogs = ActivityLog::forModel(Product::class)->get();
        $this->assertGreaterThanOrEqual(1, $productLogs->count());
        $this->assertTrue($productLogs->every(fn ($l) => $l->loggable_type === Product::class));

        $userLogs = ActivityLog::forModel(User::class)->get();
        $this->assertSame(1, $userLogs->count());
    }

    public function test_scope_between_dates_filters_correctly(): void
    {
        $product = Product::factory()->create();

        $log = ActivityLog::logCreated($product);
        $log->forceFill(['created_at' => now()->subDays(10)])->save();

        ActivityLog::logCreated($product); // hoy

        $range = ActivityLog::betweenDates(
            now()->subDays(15)->toDateString(),
            now()->subDays(5)->toDateString(),
        )->get();

        $this->assertSame(1, $range->count());
    }

    // ──────────────────────────────────────────────────────────────
    // RELATIONS
    // ──────────────────────────────────────────────────────────────

    public function test_activity_log_belongs_to_user(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($user);
        $product = Product::factory()->create();

        $log = ActivityLog::logCreated($product);

        $this->assertSame($user->id, $log->user->id);
    }

    public function test_activity_log_morphs_to_correct_model(): void
    {
        $product = Product::factory()->create();

        $log = ActivityLog::logCreated($product);

        $this->assertInstanceOf(Product::class, $log->loggable);
        $this->assertSame($product->id, $log->loggable->id);
    }

    // ──────────────────────────────────────────────────────────────
    // CONSTANTS
    // ──────────────────────────────────────────────────────────────

    public function test_all_action_constants_are_defined(): void
    {
        $this->assertSame('created',   ActivityLog::ACTION_CREATED);
        $this->assertSame('updated',   ActivityLog::ACTION_UPDATED);
        $this->assertSame('deleted',   ActivityLog::ACTION_DELETED);
        $this->assertSame('approved',  ActivityLog::ACTION_APPROVED);
        $this->assertSame('rejected',  ActivityLog::ACTION_REJECTED);
        $this->assertSame('completed', ActivityLog::ACTION_COMPLETED);
        $this->assertSame('cancelled', ActivityLog::ACTION_CANCELLED);
        $this->assertSame('exported',  ActivityLog::ACTION_EXPORTED);
        $this->assertSame('imported',  ActivityLog::ACTION_IMPORTED);
        $this->assertSame('login',     ActivityLog::ACTION_LOGIN);
        $this->assertSame('logout',    ActivityLog::ACTION_LOGOUT);
    }

    public function test_no_updated_at_column(): void
    {
        $this->assertNull(ActivityLog::UPDATED_AT);
    }
}
