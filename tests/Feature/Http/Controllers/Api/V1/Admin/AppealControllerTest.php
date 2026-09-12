<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Admin;

use Tests\Fakes\InMemoryDynamoDb;
use Tests\TestCase;

class AppealControllerTest extends TestCase
{
    public function test_lists_open_appeals(): void
    {
        $db = InMemoryDynamoDb::bind($this);
        $db->seed([
            'PK' => 'SUBMISSION#s001',
            'SK' => 'APPEAL#ap001',
            'comment' => 'Open appeal',
            'status' => 'open',
        ]);
        $db->seed([
            'PK' => 'SUBMISSION#s002',
            'SK' => 'APPEAL#ap002',
            'comment' => 'Already done',
            'status' => 'resolved',
        ]);

        $response = $this->withAdminAuth()->getJson('/api/v1/admins/appeals');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.appeal_id', 'ap001');
    }
}
