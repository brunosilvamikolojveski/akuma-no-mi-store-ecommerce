<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'total' => $this->total,
            'shipping_address' => [
                'recipient_name' => $this->shipping_recipient,
                'postal_code' => $this->shipping_postal_code,
                'state' => $this->shipping_state,
                'city' => $this->shipping_city,
                'neighborhood' => $this->shipping_neighborhood,
                'street' => $this->shipping_street,
                'number' => $this->shipping_number,
                'complement' => $this->shipping_complement,
            ],
            'items' => OrderItemResource::collection($this->items),
            'payments' => PaymentResource::collection($this->payments),
            'placed_at' => $this->placed_at?->toISOString(),
        ];
    }
}
