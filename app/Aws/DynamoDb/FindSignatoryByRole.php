<?php

namespace App\Aws\DynamoDb;

use Illuminate\Support\Str;

final class FindSignatoryByRole
{
    public function __construct(private OrganizationRecords $organizations) {}

    public function handle(string $organizationId, string $role): ?string
    {
        $organization = $this->organizations->get($organizationId);

        if ($organization === null) {
            return null;
        }

        $wanted = Str::lower($role);

        foreach ($this->organizations->desks($organization) as $desk) {
            if ($desk['role'] === $wanted) {
                return $desk['signatory_id'];
            }
        }

        return null;
    }
}
