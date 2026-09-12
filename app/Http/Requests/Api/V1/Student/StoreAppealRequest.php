<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string', 'max:64'],
            'submission_id' => ['required', 'string', 'max:64'],
            'comment' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
