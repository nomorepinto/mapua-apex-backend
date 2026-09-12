<?php

namespace App\Aws\DynamoDb;

final class FindSignatoryByRole
{
    public function __construct(private DynamoDbItems $items) {}

    public function handle(string $organizationId, string $role): ?string
    {
        $matches = $this->items->query([
            'IndexName' => 'GSI4',
            'KeyConditionExpression' => 'GSI4PK = :role',
            'ExpressionAttributeValues' => [
                ':role' => ['S' => DynamoKeys::roleIndex($role, $organizationId)],
            ],
            'Limit' => 1,
        ], allPages: false);

        $pk = $matches[0]['PK'] ?? null;

        return DynamoKeys::strip($pk, 'SIGNATORY#');
    }
}
