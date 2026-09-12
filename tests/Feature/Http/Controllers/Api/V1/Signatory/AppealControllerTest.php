<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Signatory;

use Tests\Fakes\InMemoryDynamoDb;
use Tests\Support\DynamoFixtures;
use Tests\TestCase;

class AppealControllerTest extends TestCase
{
    public function test_lists_appeals_routed_to_the_signatory(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        $db->seed([
            'PK' => 'SUBMISSION#s001',
            'SK' => 'APPEAL#ap001',
            'event_id' => 'e001',
            'sent_at' => '2026-09-12T10:00:00Z',
            'signatory_destination' => 'SIGNATORY#dean001',
            'comment' => 'Please reconsider.',
            'status' => 'open',
            'GSI3PK' => 'SIGNATORY#dean001',
            'GSI3SK' => '2026-09-12T10:00:00Z',
        ]);

        $response = $this->withSignatoryAuth([
            'custom:signatory_id' => 'dean001',
        ])->getJson('/api/v1/signatories/appeals');

        $response->assertOk()
            ->assertJsonPath('data.0.appeal_id', 'ap001');
    }

    public function test_resolves_an_appeal_and_drops_it_from_the_queue(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::submission($db, ['status' => 'denied']);
        $db->seed([
            'PK' => 'SUBMISSION#s001',
            'SK' => 'APPEAL#ap001',
            'event_id' => 'e001',
            'sent_at' => '2026-09-12T10:00:00Z',
            'signatory_destination' => 'SIGNATORY#dean001',
            'comment' => 'Please reconsider.',
            'status' => 'open',
            'GSI3PK' => 'SIGNATORY#dean001',
            'GSI3SK' => '2026-09-12T10:00:00Z',
        ]);

        $response = $this->withSignatoryAuth([
            'custom:signatory_id' => 'dean001',
        ])->postJson('/api/v1/signatories/events/e001/submissions/s001/appeals/ap001/resolve', [
            'resolution' => 'overturned',
            'comment' => 'Budget correction accepted.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.resolution', 'overturned');

        $appeal = $db->find('SUBMISSION#s001', 'APPEAL#ap001');
        $this->assertArrayNotHasKey('GSI3PK', $appeal ?? []);

        $submission = $db->find('EVENT#e001', 'SUBMISSION#s001');
        $this->assertSame('pending', $submission['status'] ?? null);
        $this->assertSame('SIGNATORY#dean001', $submission['GSI2PK'] ?? null);
    }
}
