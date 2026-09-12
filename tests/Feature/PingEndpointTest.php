<?php

namespace Tests\Feature;

use Tests\TestCase;

class PingEndpointTest extends TestCase
{
    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_token_is_invalid(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => 'invalid-token'])->getJson('/api/ping');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_bearer_token_is_used_instead_of_api_key(): void
    {
        $response = $this->withToken('testing-student-token')->getJson('/api/ping');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_signatory_key_is_used(): void
    {
        $response = $this->withApiKey('signatory')->getJson('/api/ping');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_no_api_tokens_are_configured(): void
    {
        config(['services.api.tokens.student' => '']);

        $response = $this->withApiKey()->getJson('/api/ping');

        $response->assertUnauthorized();
    }

    public function test_returns_ok_when_token_is_valid(): void
    {
        $response = $this->withApiKey()->getJson('/api/ping');

        $response->assertOk()
            ->assertJsonPath('ok', true);
    }
}
