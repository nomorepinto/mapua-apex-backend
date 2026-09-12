<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Admin;

use Tests\Fakes\InMemoryDynamoDb;
use Tests\Support\DynamoFixtures;
use Tests\TestCase;

class SubmissionControllerTest extends TestCase
{
    public function test_returns_401_when_student_key_is_used(): void
    {
        $this->fakeCognitoJwt([
            'cognito:groups' => ['admin'],
            'custom:organization_id' => '',
        ]);

        $response = $this->withApiKey('student')
            ->withToken('fake-jwt')
            ->getJson('/api/v1/admins/submissions');

        $response->assertUnauthorized();
    }

    public function test_lists_all_submissions(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::submission($db);

        $response = $this->withAdminAuth()->getJson('/api/v1/admins/submissions');

        $response->assertOk()
            ->assertJsonPath('data.0.submission_id', 's001');
    }

    public function test_returns_submission_detail_with_notifications_and_appeals(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::submission($db);
        $db->seed([
            'PK' => 'SUBMISSION#s001',
            'SK' => 'NOTIFICATION#2026-09-11T08:30:00Z',
            'signatory' => 'SIGNATORY#adv001',
            'notif_type' => 'denied',
            'comment' => 'Fix the budget.',
        ]);
        $db->seed([
            'PK' => 'SUBMISSION#s001',
            'SK' => 'APPEAL#ap001',
            'event_id' => 'e001',
            'comment' => 'Please reconsider.',
            'status' => 'open',
        ]);

        $response = $this->withAdminAuth()->getJson('/api/v1/admins/events/e001/submissions/s001');

        $response->assertOk()
            ->assertJsonPath('data.submission_id', 's001')
            ->assertJsonPath('data.notifications.0.notif_type', 'denied')
            ->assertJsonPath('data.appeals.0.appeal_id', 'ap001');
    }
}
