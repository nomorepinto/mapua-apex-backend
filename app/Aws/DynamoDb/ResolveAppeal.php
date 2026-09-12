<?php

namespace App\Aws\DynamoDb;

final class ResolveAppeal
{
    public function __construct(
        private DynamoDbItems $items,
        private GetSubmission $submissions,
    ) {}

    /**
     * Adds status/resolution fields that the APPEALS item did not originally define.
     *
     * Overturned appeals reopen the submission onto the reviewing signatory's GSI2 queue.
     *
     * @return array<string, mixed>
     */
    public function handle(
        string $signatoryId,
        string $eventId,
        string $submissionId,
        string $appealId,
        string $resolution,
        string $comment,
    ): array {
        $appeal = $this->items->get(DynamoKeys::submission($submissionId), DynamoKeys::appeal($appealId));

        if ($appeal === null || ($appeal['signatory_destination'] ?? null) !== DynamoKeys::signatory($signatoryId)) {
            abort(404);
        }

        if (($appeal['status'] ?? 'open') !== 'open') {
            abort(422, 'This appeal has already been resolved.');
        }

        $now = DynamoKeys::now();

        $this->items->patch(
            DynamoKeys::submission($submissionId),
            DynamoKeys::appeal($appealId),
            [
                'status' => 'resolved',
                'resolution' => $resolution,
                'resolved_at' => $now,
                'resolved_comment' => $comment,
            ],
            ['GSI3PK', 'GSI3SK'],
        );

        if ($resolution === 'overturned') {
            $submission = $this->submissions->require($eventId, $submissionId);
            $destination = DynamoKeys::signatory($signatoryId);

            $this->items->patch(DynamoKeys::event($eventId), DynamoKeys::submission($submissionId), [
                'status' => 'pending',
                'current_signatory' => $destination,
                'GSI2PK' => $destination,
                'GSI2SK' => $now,
            ]);

            unset($submission);
        }

        return array_merge($appeal, [
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_at' => $now,
            'resolved_comment' => $comment,
        ]);
    }
}
