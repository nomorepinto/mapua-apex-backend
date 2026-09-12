<?php

namespace App\Aws\DynamoDb;

use Illuminate\Support\Str;

final class OrganizationRecords
{
    public function __construct(private DynamoDbItems $items) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->items->scan([
            'FilterExpression' => 'begins_with(PK, :pk) AND PK = SK',
            'ExpressionAttributeValues' => [
                ':pk' => ['S' => 'ORGANIZATION#'],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name): array
    {
        $id = (string) Str::uuid();
        $key = DynamoKeys::organization($id);
        $item = [
            'PK' => $key,
            'SK' => $key,
            'name' => $name,
        ];

        $this->items->put($item);

        return $item;
    }
}
