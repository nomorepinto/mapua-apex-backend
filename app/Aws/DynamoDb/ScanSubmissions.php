<?php

namespace App\Aws\DynamoDb;

final class ScanSubmissions
{
    public function __construct(private DynamoDbItems $items) {}

    /**
     * Admin list-all. There is no all-submissions GSI, so this scans SK prefixes.
     *
     * @param  array{status?: string|null, activity_type?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function handle(array $filters = []): array
    {
        $items = $this->items->scan([
            'FilterExpression' => 'begins_with(SK, :sk)',
            'ExpressionAttributeValues' => [
                ':sk' => ['S' => 'SUBMISSION#'],
            ],
        ]);

        $status = $filters['status'] ?? null;
        $activityType = $filters['activity_type'] ?? null;

        return array_values(array_filter($items, function (array $item) use ($status, $activityType): bool {
            if (is_string($status) && $status !== '' && ($item['status'] ?? null) !== $status) {
                return false;
            }

            if (is_string($activityType) && $activityType !== '' && data_get($item, 'activity_classification.activity_type') !== $activityType) {
                return false;
            }

            return true;
        }));
    }
}
