<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MangaDetailResource extends MangaResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'available_quantity' => $this->stock,
        ];
    }
}
