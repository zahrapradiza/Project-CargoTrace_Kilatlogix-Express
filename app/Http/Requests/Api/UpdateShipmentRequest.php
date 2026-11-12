<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'integer', 'exists:users,id'],
            'carrier_id' => ['sometimes', 'integer', 'exists:carriers,id'],
            'origin_location_id' => ['sometimes', 'integer', 'exists:locations,id'],
            'destination_location_id' => ['sometimes', 'integer', 'exists:locations,id'],
            'cargo_type' => ['sometimes', 'string', 'max:100'],
            'weight_kg' => ['sometimes', 'numeric', 'min:0.01'],
            'volume_cbm' => ['sometimes', 'numeric', 'min:0'],
            'expected_departure' => ['sometimes', 'date'],
            'expected_arrival' => ['sometimes', 'date'],
            'estimated_arrival' => ['nullable', 'date'],
            'actual_arrival' => ['nullable', 'date'],
            'current_status' => ['sometimes', Rule::in(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED'])],
            'delay_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
