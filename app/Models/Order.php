<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'created_at',
        'address_id',
        'order_status',
        'payment_method',
        'payment_status',
        'total_amount',
        'updated_at',
        'user_id',
        'note',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
