<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrackingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'status' => ['required', Rule::in(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'EXCEPTION', 'DELIVERED'])],
            'event_time' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:1000'],
            'is_exception' => ['sometimes', 'boolean'],
            'estimated_arrival' => ['nullable', 'date'],
            'delay_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

