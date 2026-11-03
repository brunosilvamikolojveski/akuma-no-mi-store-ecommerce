<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'manga_id', 'manga_title', 'volume_number', 'isbn',
        'unit_price', 'quantity', 'line_total',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function manga()
    {
        return $this->belongsTo(Manga::class);
    }
}
