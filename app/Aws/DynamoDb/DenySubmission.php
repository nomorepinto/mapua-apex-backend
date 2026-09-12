<?php

namespace App\Aws\DynamoDb;

final class DenySubmission
{
    public function __construct(
        private DynamoDbItems $items,
        private GetSubmission $submissions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $signatoryId, string $eventId, string $submissionId, string $comment): array
    {
        $submission = $this->submissions->require($eventId, $submissionId);

        if (($submission['current_signatory'] ?? null) !== DynamoKeys::signatory($signatoryId)) {
            abort(404);
        }

        $now = DynamoKeys::now();

        $this->items->put([
            'PK' => DynamoKeys::submission($submissionId),
            'SK' => DynamoKeys::notification($now),
            'signatory' => DynamoKeys::signatory($signatoryId),
            'notif_type' => 'denied',
            'comment' => $comment,
        ]);

        $this->items->patch(
            DynamoKeys::event($eventId),
            DynamoKeys::submission($submissionId),
            ['status' => 'denied'],
            ['GSI2PK', 'GSI2SK'],
        );

        $submission['status'] = 'denied';
        unset($submission['GSI2PK'], $submission['GSI2SK']);

        return $submission;
    }
}
