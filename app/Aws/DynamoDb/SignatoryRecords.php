<?php

namespace App\Aws\DynamoDb;

use Illuminate\Support\Str;

final class SignatoryRecords
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
                ':pk' => ['S' => 'SIGNATORY#'],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $signatoryId): ?array
    {
        $key = DynamoKeys::signatory($signatoryId);

        return $this->items->get($key, $key);
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, string $role): array
    {
        return $this->write((string) Str::uuid(), $name, $role);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(string $signatoryId, string $name, string $role): array
    {
        if ($this->get($signatoryId) === null) {
            abort(404);
        }

        return $this->write($signatoryId, $name, $role);
    }

    /**
     * @return array<string, mixed>
     */
    private function write(string $id, string $name, string $role): array
    {
        $role = Str::lower($role);
        $key = DynamoKeys::signatory($id);
        $item = [
            'PK' => $key,
            'SK' => $key,
            'name' => $name,
            'role' => $role,
            'GSI4PK' => DynamoKeys::roleIndex($role),
            'GSI4SK' => $key,
        ];

        $this->items->put($item);

        return $item;
    }
}
