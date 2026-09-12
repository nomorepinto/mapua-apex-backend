<?php

namespace App\Http\Resources\Api\V1;

use App\Aws\DynamoDb\DynamoKeys;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = is_array($this->resource) ? $this->resource : [];

        return [
            'submission_id' => DynamoKeys::strip($item['PK'] ?? null, 'SUBMISSION#'),
            'appeal_id' => DynamoKeys::strip($item['SK'] ?? null, 'APPEAL#'),
            'event_id' => $item['event_id'] ?? null,
            'sent_at' => $item['sent_at'] ?? null,
            'signatory_destination' => DynamoKeys::strip($item['signatory_destination'] ?? null, 'SIGNATORY#'),
            'comment' => $item['comment'] ?? null,
            'status' => $item['status'] ?? 'open',
            'resolution' => $item['resolution'] ?? null,
            'resolved_at' => $item['resolved_at'] ?? null,
            'resolved_comment' => $item['resolved_comment'] ?? null,
        ];
    }
}
