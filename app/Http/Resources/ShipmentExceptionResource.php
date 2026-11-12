<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentExceptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'reported_by' => $this->reported_by,
            'reporter_name' => $this->whenLoaded('reporter', fn () => $this->reporter?->name),
            'resolved_by' => $this->resolved_by,
            'resolver_name' => $this->whenLoaded('resolver', fn () => $this->resolver?->name),
            'exception_type' => $this->exception_type,
            'severity' => $this->severity,
            'status' => $this->status,
            'description' => $this->description,
            'resolution_notes' => $this->resolution_notes,
            'detected_at' => $this->detected_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}

