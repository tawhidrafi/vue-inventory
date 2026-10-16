<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_creating_a_record_writes_an_activity_log(): void
    {
        $this->apiPost('/api/products', ['name' => 'Audited Widget', 'unit' => 'pcs'])->assertCreated();

        $log = ActivityLog::where('action', 'created')
            ->where('subject_type', Product::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->apiUser->id, $log->user_id);
        $this->assertSame($this->apiUser->name, $log->user_name);
        $this->assertStringContainsString('Audited Widget', $log->description);
    }

    public function test_updating_a_record_captures_the_changed_fields(): void
    {
        $product = $this->makeProduct(['name' => 'Before']);

        $this->apiPut("/api/products/{$product->id}", [
            'name' => 'After',
            'unit' => 'pcs',
        ])->assertOk();

        $log = ActivityLog::where('action', 'updated')
            ->where('subject_id', $product->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Before', $log->properties['changes']['name']['from']);
        $this->assertSame('After', $log->properties['changes']['name']['to']);
    }

    public function test_soft_deleting_records_a_single_deleted_entry(): void
    {
        $product = $this->makeProduct();

        // drop the "created" noise so we only see what the delete writes
        ActivityLog::query()->delete();

        $this->apiDelete("/api/products/{$product->id}")->assertNoContent();

        // the tombstone rename must not leak in as a second "updated" entry
        $this->assertSame(1, ActivityLog::where('subject_id', $product->id)->count());
        $this->assertSame('deleted', ActivityLog::where('subject_id', $product->id)->value('action'));
    }

    public function test_restoring_is_recorded(): void
    {
        $product = $this->makeProduct();

        $this->apiDelete("/api/products/{$product->id}")->assertNoContent();
        ActivityLog::query()->delete();

        $this->apiPost("/api/products/{$product->id}/restore")->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'restored',
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);
    }

    public function test_passwords_are_never_written_to_the_log(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'password' => 'super-secret-pw']);

        $log = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->properties['attributes']);
        $this->assertStringNotContainsString('super-secret-pw', json_encode($log->properties));
    }

    public function test_staff_cannot_read_activity_logs(): void
    {
        $this->authenticate('staff');

        $this->apiGet('/api/activity-logs')->assertStatus(403);
    }

    public function test_managers_can_read_activity_logs(): void
    {
        $this->authenticate('manager');

        $this->apiGet('/api/activity-logs')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }
}
