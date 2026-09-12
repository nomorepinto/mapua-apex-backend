<?php

namespace App\Aws\DynamoDb;

final class ListSignatoryAppeals
{
    public function __construct(private DynamoDbItems $items) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function handle(string $signatoryId): array
    {
        return $this->items->query([
            'IndexName' => 'GSI3',
            'KeyConditionExpression' => 'GSI3PK = :signatory',
            'ExpressionAttributeValues' => [
                ':signatory' => ['S' => DynamoKeys::signatory($signatoryId)],
            ],
        ]);
    }
}
