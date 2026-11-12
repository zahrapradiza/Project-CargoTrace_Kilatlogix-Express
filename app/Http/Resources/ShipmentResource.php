<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_number' => $this->shipment_number,
            'customer_id' => $this->customer_id,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'carrier_id' => $this->carrier_id,
            'carrier' => new CarrierResource($this->whenLoaded('carrier')),
            'origin_location_id' => $this->origin_location_id,
            'origin_location' => new LocationResource($this->whenLoaded('originLocation')),
            'destination_location_id' => $this->destination_location_id,
            'destination_location' => new LocationResource($this->whenLoaded('destinationLocation')),
            'cargo_type' => $this->cargo_type,
            'weight_kg' => $this->weight_kg,
            'volume_cbm' => $this->volume_cbm,
            'expected_departure' => $this->expected_departure?->toIso8601String(),
            'expected_arrival' => $this->expected_arrival?->toIso8601String(),
            'estimated_arrival' => $this->estimated_arrival?->toIso8601String(),
            'actual_arrival' => $this->actual_arrival?->toIso8601String(),
            'current_status' => $this->current_status,
            'is_delayed' => $this->is_delayed,
            'delay_hours' => $this->delay_hours,
            'delay_severity' => $this->delay_severity,
            'delay_reason' => $this->delay_reason,
            'items' => ShipmentItemResource::collection($this->whenLoaded('items')),
            'tracking_events' => TrackingEventResource::collection($this->whenLoaded('trackingEvents')),
            'exceptions' => ShipmentExceptionResource::collection($this->whenLoaded('exceptions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

