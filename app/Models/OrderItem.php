<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{

    use HasFactory, softDeletes;

    protected $table = 'order_items';


    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'size',
        'color',

    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }


}
