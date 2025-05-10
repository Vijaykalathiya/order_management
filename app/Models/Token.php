<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $fillable = ['number', 'order_id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
