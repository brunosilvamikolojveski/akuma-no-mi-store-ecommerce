<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotalCents = $this->items->sum(
            fn ($item) => Money::toCents((string) $item->manga->price) * $item->quantity,
        );

        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->items),
            'subtotal' => Money::fromCents($subtotalCents),
        ];
    }
}
