<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Admin;

use Tests\Fakes\InMemoryDynamoDb;
use Tests\Support\DynamoFixtures;
use Tests\TestCase;

class OrganizationControllerTest extends TestCase
{
    public function test_lists_organizations(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::organization($db);

        $response = $this->withAdminAuth()->getJson('/api/v1/admins/organizations');

        $response->assertOk()
            ->assertJsonPath('data.0.organization_id', 'a1b2')
            ->assertJsonPath('data.0.name', 'Mapua Computing Society');
    }

    public function test_creates_an_organization(): void
    {
        $db = InMemoryDynamoDb::bind($this);

        $response = $this->withAdminAuth()->postJson('/api/v1/admins/organizations', [
            'name' => 'IEEE Mapua',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'IEEE Mapua');

        $id = $response->json('data.organization_id');
        $this->assertIsString($id);
        $this->assertNotNull($db->find('ORGANIZATION#'.$id, 'ORGANIZATION#'.$id));
    }

    public function test_returns_422_when_name_is_missing(): void
    {
        InMemoryDynamoDb::bind($this);

        $this->withAdminAuth()
            ->postJson('/api/v1/admins/organizations', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
