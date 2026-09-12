<?php

namespace Tests\Support;

use Tests\Fakes\InMemoryDynamoDb;

final class DynamoFixtures
{
    public static function organization(InMemoryDynamoDb $db, string $id = 'a1b2', string $name = 'Mapua Computing Society'): void
    {
        $db->seed([
            'PK' => 'ORGANIZATION#'.$id,
            'SK' => 'ORGANIZATION#'.$id,
            'name' => $name,
        ]);
    }

    public static function event(InMemoryDynamoDb $db, string $org = 'a1b2', string $event = 'e001'): void
    {
        $db->seed([
            'PK' => 'EVENT#'.$event,
            'SK' => 'EVENT#'.$event,
            'sent_at' => '2026-09-01T09:00:00Z',
            'GSI1PK' => 'ORGANIZATION#'.$org,
            'GSI1SK' => '2026-09-01T09:00:00Z',
        ]);
    }

    public static function signatory(
        InMemoryDynamoDb $db,
        string $id,
        string $role,
        string $org = 'a1b2',
        string $name = 'Prof. Juan Dela Cruz',
    ): void {
        $db->seed([
            'PK' => 'SIGNATORY#'.$id,
            'SK' => 'SIGNATORY#'.$id,
            'name' => $name,
            'role' => $role,
            'organization_id' => $org,
            'GSI4PK' => 'ROLE#'.strtoupper($role).'#ORG#'.$org,
            'GSI4SK' => 'SIGNATORY#'.$id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function submission(InMemoryDynamoDb $db, array $overrides = []): void
    {
        $payload = SaafPayload::valid();
        unset($payload['event_id']);

        $db->seed(array_merge([
            'PK' => 'EVENT#e001',
            'SK' => 'SUBMISSION#s001',
            'submission_type' => 'saaf',
            'sent_at' => '2026-09-10T14:00:00Z',
            'status' => 'pending',
            'current_signatory' => 'SIGNATORY#adv001',
            'GSI2PK' => 'SIGNATORY#adv001',
            'GSI2SK' => '2026-09-10T14:00:00Z',
        ], $payload, $overrides));
    }
}
