<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $unitPrice = Money::toCents((string) $this->manga->price);

        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => Money::fromCents($unitPrice),
            'line_total' => Money::fromCents($unitPrice * $this->quantity),
            'manga' => [
                'id' => $this->manga->id,
                'title' => $this->manga->title ?: $this->manga->series->title,
                'volume_number' => $this->manga->volume_number,
                'series' => $this->manga->series->title,
                'is_active' => $this->manga->is_active,
                'in_stock' => $this->manga->stock > 0,
            ],
        ];
    }
}
