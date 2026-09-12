<?php

namespace App\Aws\DynamoDb;

final class SignatorySequenceResolver
{
    public function __construct(private FindSignatoryByRole $findSignatoryByRole) {}

    /**
     * Extra-curricular activities that reserve a venue go Adviser then CDM.
     * Every other combination is Adviser only — the schema only specifies the venue case.
     *
     * @param  array<string, mixed>  $submission
     * @return list<string>
     */
    public function rolesFor(array $submission): array
    {
        $activityType = data_get($submission, 'activity_classification.activity_type');
        $hasReservation = data_get($submission, 'venue_reservation.has_reservation');

        if ($activityType === 'extra-curricular' && $hasReservation === true) {
            return ['adviser', 'cdm'];
        }

        return ['adviser'];
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
