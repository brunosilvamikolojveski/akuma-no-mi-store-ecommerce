<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manga extends Model
{
    protected $fillable = [
        'series_id', 'volume_number', 'title', 'isbn', 'description', 'price',
        'stock', 'cover_image_path', 'published_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'published_at' => 'date', 'is_active' => 'boolean'];
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
