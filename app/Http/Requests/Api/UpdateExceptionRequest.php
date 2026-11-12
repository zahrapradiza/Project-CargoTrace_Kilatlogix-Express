<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(['OPEN', 'INVESTIGATING', 'RESOLVED'])],
            'severity' => ['sometimes', Rule::in(['LOW', 'MEDIUM', 'HIGH'])],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
            'resolved_at' => ['nullable', 'date'],
        ];
    }
}
