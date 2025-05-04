<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['token_number', 'total_amount'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
