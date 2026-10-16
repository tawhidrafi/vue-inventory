<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use AuthenticatesApiToken, RefreshDatabase;

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'secret']);

        $response = $this->postJson('/api/login', [
            'email' => 'a@example.com',
            'password' => 'secret',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
        $this->assertIsString($response->json('token'));

        // only the hash is stored, never the raw token
        $this->assertDatabaseCount('api_tokens', 1);
        $this->assertDatabaseMissing('api_tokens', ['token' => $response->json('token')]);
    }

    public function test_login_fails_with_a_wrong_password(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'secret']);

        $this->postJson('/api/login', [
            'email' => 'a@example.com',
            'password' => 'nope',
        ])->assertStatus(422)->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $this->getJson('/api/products', ['Authorization' => 'Bearer not-a-real-token'])
            ->assertStatus(401);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $this->authenticate();

        $this->apiGet('/api/me')
            ->assertOk()
            ->assertJsonPath('email', $this->apiUser->email);
    }

    public function test_logout_revokes_the_token(): void
    {
        $this->authenticate();

        $this->apiPost('/api/logout')->assertNoContent();

        $this->apiGet('/api/me')->assertStatus(401);
    }
}
