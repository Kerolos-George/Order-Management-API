<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'product_name',
        'quantity',
        'price',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
