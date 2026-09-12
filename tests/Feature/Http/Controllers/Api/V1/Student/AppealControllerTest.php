<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Student;

use Tests\Fakes\InMemoryDynamoDb;
use Tests\Support\DynamoFixtures;
use Tests\TestCase;

class AppealControllerTest extends TestCase
{
    public function test_returns_422_when_filing_an_appeal_without_a_comment(): void
    {
        InMemoryDynamoDb::bind($this);

        $response = $this->withStudentAuth()->postJson('/api/v1/students/appeals', [
            'event_id' => 'e001',
            'submission_id' => 's001',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_files_an_appeal_to_the_org_dean_when_the_submission_is_denied(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::signatory($db, 'dean001', 'dean');
        DynamoFixtures::submission($db, ['status' => 'denied']);

        $response = $this->withStudentAuth()->postJson('/api/v1/students/appeals', [
            'event_id' => 'e001',
            'submission_id' => 's001',
            'comment' => 'Requesting review of the budget denial.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.signatory_destination', 'dean001')
            ->assertJsonPath('data.status', 'open');
    }

    public function test_lists_appeals_for_a_submission(): void
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

        $response = $this->withStudentAuth()->getJson('/api/v1/students/events/e001/submissions/s001/appeals');

        $response->assertOk()
            ->assertJsonPath('data.0.appeal_id', 'ap001')
            ->assertJsonPath('data.0.status', 'open');
    }
}
