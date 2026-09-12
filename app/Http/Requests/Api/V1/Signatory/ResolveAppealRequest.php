<?php

namespace App\Http\Requests\Api\V1\Signatory;

use Illuminate\Foundation\Http\FormRequest;

class ResolveAppealRequest extends FormRequest
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
            'resolution' => ['required', 'in:upheld,overturned'],
            'comment' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
