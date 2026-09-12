<?php

namespace App\Aws\DynamoDb;

final class ScanAppeals
{
    public function __construct(private DynamoDbItems $items) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function handle(bool $openOnly = true): array
    {
        $items = $this->items->scan([
            'FilterExpression' => 'begins_with(SK, :sk)',
            'ExpressionAttributeValues' => [
                ':sk' => ['S' => 'APPEAL#'],
            ],
        ]);

        if (! $openOnly) {
            return $items;
        }

        return array_values(array_filter(
            $items,
            fn (array $item): bool => ($item['status'] ?? 'open') === 'open',
        ));
    }
}
