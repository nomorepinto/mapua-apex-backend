<?php

namespace Tests\Feature;

use Tests\TestCase;

class PingEndpointTest extends TestCase
{
    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated: X-Api-Key is missing.');
    }

    public function test_returns_401_when_token_is_invalid(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => 'invalid-token'])->getJson('/api/ping');

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated: Invalid X-Api-Key for student routes.');
    }

    public function test_returns_401_when_bearer_token_is_used_instead_of_api_key(): void
    {
        $response = $this->withToken('testing-student-token')->getJson('/api/ping');

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated: X-Api-Key is missing.');
    }

    public function test_returns_401_when_signatory_key_is_used(): void
    {
        $response = $this->withApiKey('signatory')->getJson('/api/ping');

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated: Invalid X-Api-Key for student routes.');
    }

    public function test_returns_401_when_no_api_tokens_are_configured(): void
    {
        config([
            'services.api.tokens.student' => '',
            'services.api.tokens.admin' => '',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => 'any-key'])->getJson('/api/ping');

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated: API_TOKEN_STUDENT is not configured on the server.');
    }

    public function test_returns_ok_when_token_is_valid(): void
    {
        $response = $this->withApiKey()->getJson('/api/ping');

        $response->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_returns_ok_when_admin_key_is_used(): void
    {
        $response = $this->withApiKey('admin')->getJson('/api/ping');

        $response->assertOk()
            ->assertJsonPath('ok', true);
    }
}
