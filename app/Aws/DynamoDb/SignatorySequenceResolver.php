<?php

namespace App\Aws\DynamoDb;

final class SignatorySequenceResolver
{
    public function __construct(private FindSignatoryByRole $findSignatoryByRole) {}

    /**
     * Co-curricular (academic) events go Adviser → Dean → OSAAR.
     * Extra-curricular events skip Dean: Adviser → OSAAR.
     * A venue reservation then adds CDM after OSAAR.
     *
     * @param  array<string, mixed>  $submission
     * @return list<string>
     */
    public function rolesFor(array $submission): array
    {
        $roles = ['adviser'];

        if (data_get($submission, 'activity_classification.activity_type') === 'co-curricular') {
            $roles[] = 'dean';
        }

        $roles[] = 'osaar';

        if (data_get($submission, 'venue_reservation.has_reservation') === true) {
            $roles[] = 'cdm';
        }

        return $roles;
    }

    /**
     * @param  array<string, mixed>  $submission
     * @return list<string>
     */
    public function signatoryIds(string $organizationId, array $submission): array
    {
        $ids = [];

        foreach ($this->rolesFor($submission) as $role) {
            $signatoryId = $this->findSignatoryByRole->handle($organizationId, $role);

            if ($signatoryId === null) {
                throw new UnresolvableSignatoryRoute("No {$role} signatory is assigned for this organization.");
            }

            $ids[] = $signatoryId;
        }

        return $ids;
    }
}
