<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Admin;

use Tests\Fakes\InMemoryDynamoDb;
use Tests\Support\DynamoFixtures;
use Tests\TestCase;

class SignatoryControllerTest extends TestCase
{
    public function test_lists_signatories(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::signatory($db, 'adv001', 'adviser');

        $response = $this->withAdminAuth()->getJson('/api/v1/admins/signatories');

        $response->assertOk()
            ->assertJsonPath('data.0.signatory_id', 'adv001')
            ->assertJsonPath('data.0.role', 'adviser');
    }

    public function test_creates_a_signatory_with_a_role_index(): void
    {
        $db = InMemoryDynamoDb::bind($this);

        $response = $this->withAdminAuth()->postJson('/api/v1/admins/signatories', [
            'name' => 'Prof. Juan Dela Cruz',
            'role' => 'adviser',
            'organization_id' => 'a1b2',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.role', 'adviser')
            ->assertJsonPath('data.organization_id', 'a1b2');

        $id = $response->json('data.signatory_id');
        $stored = $db->find('SIGNATORY#'.$id, 'SIGNATORY#'.$id);
        $this->assertSame('ROLE#ADVISER#ORG#a1b2', $stored['GSI4PK'] ?? null);
    }

    public function test_updates_a_signatory_role_without_rewriting_notifications(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::signatory($db, 'adv001', 'adviser');
        $db->seed([
            'PK' => 'SUBMISSION#s001',
            'SK' => 'NOTIFICATION#2026-09-11T08:30:00Z',
            'signatory' => 'SIGNATORY#adv001',
            'notif_type' => 'denied',
            'comment' => 'historical snapshot',
        ]);

        $response = $this->withAdminAuth()->putJson('/api/v1/admins/signatories/adv001', [
            'name' => 'Prof. Juan Dela Cruz',
            'role' => 'dean',
            'organization_id' => 'a1b2',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.role', 'dean');

        $notification = $db->find('SUBMISSION#s001', 'NOTIFICATION#2026-09-11T08:30:00Z');
        $this->assertSame('SIGNATORY#adv001', $notification['signatory'] ?? null);
    }
}
