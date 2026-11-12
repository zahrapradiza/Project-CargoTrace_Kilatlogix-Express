<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'location_id' => $this->location_id,
            'location' => new LocationResource($this->whenLoaded('location')),
            'created_by' => $this->created_by,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'status' => $this->status,
            'event_time' => $this->event_time?->toIso8601String(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'is_exception' => $this->is_exception,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
