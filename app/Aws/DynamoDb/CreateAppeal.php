<?php

namespace App\Aws\DynamoDb;

use Illuminate\Support\Str;

final class CreateAppeal
{
    public function __construct(
        private DynamoDbItems $items,
        private GetEvent $events,
        private GetSubmission $submissions,
        private FindSignatoryByRole $findSignatoryByRole,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $organizationId, string $eventId, string $submissionId, string $comment): array
    {
        $this->events->forOrganization($eventId, $organizationId);
        $submission = $this->submissions->require($eventId, $submissionId);

        if (($submission['status'] ?? null) !== 'denied') {
            abort(422, 'Appeals can only be filed against a denied submission.');
        }

        $deanId = $this->findSignatoryByRole->handle($organizationId, 'dean');

        if ($deanId === null) {
            throw new UnresolvableSignatoryRoute('No dean signatory is assigned for this organization.');
        }

        $sentAt = DynamoKeys::now();
        $destination = DynamoKeys::signatory($deanId);
        $item = [
            'PK' => DynamoKeys::submission($submissionId),
            'SK' => DynamoKeys::appeal((string) Str::uuid()),
            'event_id' => DynamoKeys::strip($eventId, 'EVENT#'),
            'sent_at' => $sentAt,
            'signatory_destination' => $destination,
            'comment' => $comment,
            'status' => 'open',
            'GSI3PK' => $destination,
            'GSI3SK' => $sentAt,
        ];

        $this->items->put($item);

        return $item;
    }
}
