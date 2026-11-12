<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'weight_kg' => $this->weight_kg,
            'declared_value' => $this->declared_value,
        ];
    }
}
