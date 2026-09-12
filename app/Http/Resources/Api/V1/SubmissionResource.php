<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = is_array($this->resource) ? $this->resource : [];

        return [
            'event_id' => $this->idAfterPrefix($item['PK'] ?? null, 'EVENT#'),
            'submission_id' => $this->idAfterPrefix($item['SK'] ?? null, 'SUBMISSION#'),
            'submission_type' => $item['submission_type'] ?? null,
            'sent_at' => $item['sent_at'] ?? null,
            'status' => $item['status'] ?? null,
            'current_signatory' => $this->idAfterPrefix($item['current_signatory'] ?? null, 'SIGNATORY#'),
            'activity_classification' => $item['activity_classification'] ?? null,
            'proponents' => $item['proponents'] ?? [],
            'activity_details' => $item['activity_details'] ?? null,
            'institutional_alignment' => $item['institutional_alignment'] ?? null,
            'detailed_budget_proposal' => $item['detailed_budget_proposal'] ?? null,
            'venue_reservation' => $item['venue_reservation'] ?? null,
        ];
    }

    private function idAfterPrefix(mixed $value, string $prefix): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return Str::chopStart($value, $prefix);
    }
}
