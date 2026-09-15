<?php

namespace App\Aws\DynamoDb;

final class ReturnSubmission
{
    public function __construct(
        private DynamoDbItems $items,
        private GetSubmission $submissions,
    ) {}

    /**
     * Send the paper back for revision. GSI2 stays so the same desk still sees it.
     *
     * @return array<string, mixed>
     */
    public function handle(string $signatoryId, string $eventId, string $submissionId, string $comment): array
    {
        $submission = $this->submissions->require($eventId, $submissionId);
        SignatoryDesk::requireOpen($submission, $signatoryId);

        $now = DynamoKeys::now();

        $this->items->put([
            'PK' => DynamoKeys::submission($submissionId),
            'SK' => DynamoKeys::notification($now),
            'signatory' => DynamoKeys::signatory($signatoryId),
            'notif_type' => 'returned',
            'comment' => $comment,
        ]);

        $this->items->patch(
            DynamoKeys::event($eventId),
            DynamoKeys::submission($submissionId),
            ['status' => 'returned'],
        );

        $submission['status'] = 'returned';

        return $submission;
    }
}
