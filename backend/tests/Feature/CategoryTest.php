<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_a_category_can_be_created(): void
    {
        $this->apiPost('/api/categories', ['name' => 'Electronics'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Electronics');

        $this->apiPost('/api/categories', ['name' => 'Electronics'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_delete_frees_the_name_for_reuse(): void
    {
        $category = $this->makeCategory(['name' => 'Audio']);

        $this->apiDelete("/api/categories/{$category->id}")->assertNoContent();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => "Audio::deleted::{$category->id}",
        ]);

        $this->apiPost('/api/categories', ['name' => 'Audio'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Audio');
    }

    public function test_restore_brings_back_the_original_name(): void
    {
        $category = $this->makeCategory(['name' => 'Toys']);

        $this->apiDelete("/api/categories/{$category->id}")->assertNoContent();

        $this->apiPost("/api/categories/{$category->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.name', 'Toys');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Toys', 'deleted_at' => null]);
    }

    public function test_restore_fails_when_the_name_has_been_taken(): void
    {
        $category = $this->makeCategory(['name' => 'Books']);

        $this->apiDelete("/api/categories/{$category->id}");

        $this->makeCategory(['name' => 'Books']);

        $this->apiPost("/api/categories/{$category->id}/restore")->assertStatus(422);
    }

    public function test_a_category_with_products_cannot_be_deleted(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct(['category_id' => $category->id]);

        $this->apiDelete("/api/categories/{$category->id}")->assertStatus(422);
    }

    public function test_force_delete_removes_an_unused_category(): void
    {
        $category = $this->makeCategory();

        $this->apiDelete("/api/categories/{$category->id}")->assertNoContent();
        $this->apiDelete("/api/categories/{$category->id}/force")->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
