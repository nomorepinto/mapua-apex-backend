<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Student;

use Tests\Fakes\InMemoryDynamoDb;
use Tests\Support\DynamoFixtures;
use Tests\Support\SaafPayload;
use Tests\TestCase;

class SubmissionControllerTest extends TestCase
{
    public function test_returns_401_when_api_key_is_missing(): void
    {
        $this->fakeCognitoJwt();

        $response = $this->withToken('fake-jwt')->getJson('/api/v1/students/submissions');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_signatory_key_is_used(): void
    {
        $this->fakeCognitoJwt();

        $response = $this->withApiKey('signatory')
            ->withToken('fake-jwt')
            ->getJson('/api/v1/students/submissions');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_admin_omits_organization(): void
    {
        InMemoryDynamoDb::bind($this);

        $response = $this->withAdminAuth()->getJson('/api/v1/students/submissions');

        $response->assertUnauthorized();
    }

    public function test_lists_submissions_when_admin_sends_an_organization_header(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::submission($db);

        $response = $this->withAdminAuth()
            ->withHeaders(['X-Organization-Id' => 'a1b2'])
            ->getJson('/api/v1/students/submissions');

        $response->assertOk()
            ->assertJsonPath('data.0.submission_id', 's001');
    }

    public function test_ignores_organization_header_for_a_student_jwt(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db, org: 'other-org');
        DynamoFixtures::submission($db);

        $response = $this->withStudentAuth()
            ->withHeaders(['X-Organization-Id' => 'other-org'])
            ->getJson('/api/v1/students/events/e001/submissions/s001');

        $response->assertNotFound();
    }

    public function test_returns_401_when_jwt_is_missing(): void
    {
        $this->fakeCognitoJwt();

        $response = $this->withApiKey()->getJson('/api/v1/students/submissions');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_jwt_is_invalid(): void
    {
        $response = $this->withStudentAuth(jwt: 'invalid-jwt')->getJson('/api/v1/students/submissions');

        $response->assertUnauthorized();
    }

    public function test_returns_submissions_for_the_jwt_organization(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::submission($db);

        $response = $this->withStudentAuth()->getJson('/api/v1/students/submissions');

        $response->assertOk()
            ->assertJsonPath('data.0.event_id', 'e001')
            ->assertJsonPath('data.0.submission_id', 's001')
            ->assertJsonPath('data.0.submission_type', 'saaf')
            ->assertJsonPath('data.0.current_signatory', 'adv001')
            ->assertJsonPath('data.0.activity_details.title_and_nature', 'Hack Night: Intro to Web Dev');
    }

    public function test_returns_an_empty_collection_when_the_organization_has_no_submissions(): void
    {
        InMemoryDynamoDb::bind($this);

        $response = $this->withStudentAuth()->getJson('/api/v1/students/submissions');

        $response->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_returns_404_when_submission_belongs_to_another_organization(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db, org: 'other-org');
        DynamoFixtures::submission($db);

        $response = $this->withStudentAuth()->getJson('/api/v1/students/events/e001/submissions/s001');

        $response->assertNotFound();
    }

    public function test_returns_one_submission_for_the_jwt_organization(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::submission($db);

        $response = $this->withStudentAuth()->getJson('/api/v1/students/events/e001/submissions/s001');

        $response->assertOk()
            ->assertJsonPath('data.submission_id', 's001')
            ->assertJsonPath('data.event_id', 'e001');
    }

    public function test_returns_422_when_create_payload_is_empty(): void
    {
        InMemoryDynamoDb::bind($this);

        $response = $this->withStudentAuth()->postJson('/api/v1/students/submissions', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['event_id', 'submission_type', 'activity_classification', 'proponents']);
    }

    public function test_creates_a_submission_and_routes_to_the_adviser(): void
    {
        $this->freezeTime();
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::signatory($db, 'adv001', 'adviser');
        DynamoFixtures::signatory($db, 'cdm001', 'cdm');

        $response = $this->withStudentAuth()->postJson('/api/v1/students/submissions', SaafPayload::valid());

        $response->assertCreated()
            ->assertJsonPath('data.event_id', 'e001')
            ->assertJsonPath('data.current_signatory', 'adv001')
            ->assertJsonPath('data.status', 'pending');

        $submissionId = $response->json('data.submission_id');
        $this->assertIsString($submissionId);
        $stored = $db->find('EVENT#e001', 'SUBMISSION#'.$submissionId);
        $this->assertSame('SIGNATORY#adv001', $stored['GSI2PK'] ?? null);
    }

    public function test_updates_a_denied_submission_and_reopens_the_queue(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        DynamoFixtures::event($db);
        DynamoFixtures::signatory($db, 'adv001', 'adviser');
        DynamoFixtures::signatory($db, 'cdm001', 'cdm');
        DynamoFixtures::submission($db, [
            'status' => 'denied',
        ]);

        $payload = SaafPayload::valid();
        unset($payload['event_id']);

        $response = $this->withStudentAuth()->putJson(
            '/api/v1/students/events/e001/submissions/s001',
            $payload,
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.current_signatory', 'adv001');
    }
}
