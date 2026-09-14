<?php

namespace App\Aws\DynamoDb;

use Illuminate\Support\Str;

final class SignatoryRecords
{
    public function __construct(
        private DynamoDbItems $items,
        private OrganizationRecords $organizations,
    ) {}

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
    public function create(string $name, string $role, string $organizationId): array
    {
        $id = (string) Str::uuid();

        return $this->write($id, $name, $role, $organizationId);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(string $signatoryId, string $name, string $role, string $organizationId): array
    {
        $existing = $this->get($signatoryId);

        if ($existing === null) {
            abort(404);
        }

        return $this->write($signatoryId, $name, $role, $organizationId, $existing);
    }

    /**
     * @param  array<string, mixed>|null  $existing
     * @return array<string, mixed>
     */
    private function write(string $id, string $name, string $role, string $organizationId, ?array $existing = null): array
    {
        $this->organizations->require($organizationId);

        $role = Str::lower($role);
        $organizationId = DynamoKeys::strip($organizationId, 'ORGANIZATION#') ?? $organizationId;
        $previousOrganizationId = DynamoKeys::strip($existing['organization_id'] ?? null, 'ORGANIZATION#');

        $key = DynamoKeys::signatory($id);
        $item = [
            'PK' => $key,
            'SK' => $key,
            'name' => $name,
            'role' => $role,
            'organization_id' => $organizationId,
            'GSI4PK' => DynamoKeys::roleIndex($role, $organizationId),
            'GSI4SK' => $key,
        ];

        $this->items->put($item);

        if (is_string($previousOrganizationId) && $previousOrganizationId !== '' && $previousOrganizationId !== $organizationId) {
            $this->organizations->detach($previousOrganizationId, $id);
        }

        $this->organizations->assign($organizationId, $role, $id);

        return $item;
    }
}
