<?php

namespace Tests\Feature;

use Tests\TestCase;

class HelloEndpointTest extends TestCase
{
    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->getJson('/api/hello');

        $response->assertUnauthorized();
    }

    public function test_returns_hello_json_when_token_is_valid(): void
    {
        $response = $this->withApiKey()->getJson('/api/hello');

        $response->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertIsString($response->json('message'));
    }
}
