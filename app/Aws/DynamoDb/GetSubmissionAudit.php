<?php

namespace App\Aws\DynamoDb;

final class GetSubmissionAudit
{
    public function __construct(
        private GetSubmission $submissions,
        private ListSubmissionNotifications $notifications,
        private ListSubmissionAppeals $appeals,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(string $eventId, string $submissionId): array
    {
        $submission = $this->submissions->require($eventId, $submissionId);
        $submission['notifications'] = $this->notifications->handle($submissionId);
        $submission['appeals'] = $this->appeals->handle($submissionId);

        return $submission;
    }
}
