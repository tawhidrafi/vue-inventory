<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use AuthenticatesApiToken, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate('admin');
    }

    public function test_an_admin_can_create_a_user(): void
    {
        $this->apiPost('/api/users', [
            'name' => 'Pat Manager',
            'email' => 'pat@example.com',
            'password' => 'password123',
            'role' => 'manager',
        ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'pat@example.com')
            ->assertJsonPath('data.role', 'manager');

        $this->assertDatabaseHas('users', ['email' => 'pat@example.com', 'role' => 'manager']);
    }

    public function test_the_role_must_be_valid(): void
    {
        $this->apiPost('/api/users', [
            'name' => 'Bad Role',
            'email' => 'bad@example.com',
            'password' => 'password123',
            'role' => 'superuser',
        ])->assertStatus(422)->assertJsonValidationErrors('role');
    }

    public function test_a_users_role_can_be_changed(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->apiPut("/api/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'manager',
        ])->assertOk()->assertJsonPath('data.role', 'manager');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'manager']);
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $this->apiDelete("/api/users/{$this->apiUser->id}")->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $this->apiUser->id]);
    }

    public function test_a_second_admin_can_be_created_and_managed(): void
    {
        $other = User::factory()->create(['role' => 'manager']);

        $this->apiPut("/api/users/{$other->id}", [
            'name' => $other->name,
            'email' => $other->email,
            'role' => 'admin',
        ])->assertOk();

        $this->apiDelete("/api/users/{$other->id}")->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_creating_a_user_requires_a_password(): void
    {
        $this->apiPost('/api/users', [
            'name' => 'No Password',
            'email' => 'nopass@example.com',
            'role' => 'staff',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }
}
