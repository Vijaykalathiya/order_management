<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'item_code', 'name', 'qty', 'price', 'station'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
