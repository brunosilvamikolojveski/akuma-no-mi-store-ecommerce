<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'status', 'subtotal', 'shipping_cost', 'total', 'shipping_recipient',
        'shipping_postal_code', 'shipping_state', 'shipping_city', 'shipping_neighborhood',
        'shipping_street', 'shipping_number', 'shipping_complement', 'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'shipping_cost' => 'decimal:2', 'total' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
