<?php

namespace Tests\Concerns;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait AuthenticatesApiToken
{
    protected User $apiUser;

    protected string $apiToken;

    /**
     * Create a user and a valid API token, and remember them for requests.
     */
    protected function authenticate(string $role = 'admin'): static
    {
        $this->apiUser = User::factory()->create(['role' => $role]);

        $plain = Str::random(64);

        ApiToken::create([
            'user_id' => $this->apiUser->id,
            'token' => hash('sha256', $plain),
            'expires_at' => now()->addDays(7),
        ]);

        $this->apiToken = $plain;

        return $this;
    }

    protected function authHeaders(?string $token = null): array
    {
        return ['Authorization' => 'Bearer '.($token ?? $this->apiToken)];
    }

    protected function apiGet(string $uri, array $query = []): TestResponse
    {
        $url = $query ? $uri.'?'.http_build_query($query) : $uri;

        return $this->getJson($url, $this->authHeaders());
    }

    protected function apiPost(string $uri, array $data = []): TestResponse
    {
        return $this->postJson($uri, $data, $this->authHeaders());
    }

    protected function apiPut(string $uri, array $data = []): TestResponse
    {
        return $this->putJson($uri, $data, $this->authHeaders());
    }

    protected function apiDelete(string $uri): TestResponse
    {
        return $this->deleteJson($uri, [], $this->authHeaders());
    }
}
