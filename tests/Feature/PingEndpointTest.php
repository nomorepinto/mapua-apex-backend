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
        $response = $this->withToken('invalid-token')->getJson('/api/ping');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_no_api_tokens_are_configured(): void
    {
        config(['services.api.tokens' => []]);

        $response = $this->withToken('testing-token')->getJson('/api/ping');

        $response->assertUnauthorized();
    }

    public function test_returns_ok_when_token_is_valid(): void
    {
        $response = $this->withToken('testing-token')->getJson('/api/ping');

        $response->assertOk()
            ->assertJsonPath('ok', true);
    }
}
