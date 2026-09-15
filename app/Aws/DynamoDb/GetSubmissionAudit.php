<?php

namespace App\Aws\DynamoDb;

final class GetSubmissionAudit
{
    public function __construct(
        private GetSubmission $submissions,
        private ListSubmissionNotifications $notifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $eventId, string $submissionId): array
    {
        $submission = $this->submissions->require($eventId, $submissionId);
        $submission['notifications'] = $this->notifications->handle($submissionId);

        return $submission;
    }
}
