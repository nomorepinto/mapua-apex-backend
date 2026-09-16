<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Student;

use Tests\Fakes\InMemoryDynamoDb;
use Tests\Support\DynamoFixtures;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    public function test_returns_401_when_jwt_is_missing(): void
    {
        $this->fakeCognitoJwt();

        $response = $this->getJson('/api/v1/students/events/e001/submissions/s001/notifications');

        $response->assertUnauthorized();
    }

    public function test_returns_notifications_for_a_submission_in_the_jwt_organization(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::submission($db);
        $db->seed([
            'PK' => 'SUBMISSION#s001',
            'SK' => 'NOTIFICATION#2026-09-11T08:30:00Z',
            'signatory' => 'SIGNATORY#adv001',
            'notif_type' => 'denied',
            'comment' => 'Budget is incomplete.',
        ]);

        $response = $this->withStudentAuth()->getJson('/api/v1/students/events/e001/submissions/s001/notifications');

        $response->assertOk()
            ->assertJsonPath('data.0.notif_type', 'denied')
            ->assertJsonPath('data.0.comment', 'Budget is incomplete.');
    }
}
