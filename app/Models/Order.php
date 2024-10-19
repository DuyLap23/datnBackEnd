<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Order extends Model
{
    use HasFactory, softDeletes;
    protected $fillable = [
        'user_id',
        'address_id',
        'payment_method',
        'payment_status',
        'order_status',
        'total_amount',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
//['pending', 'completed', 'failed']
    const STARTUS_ORDER =   [
        'pending' => 'Chờ xác nhận',
        'comfirmed' => 'Đã xác nhận',
        'preparing' => 'Đang chuẩn bị hàng ',
        'shipping' => 'Đang vận chuyển',
        'delevered' => 'Đã giao hàng',
        'cancel' => 'Đã hủy'
    ];
    const STARTUS_PAYMENT =   [
        'unpaid' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại'
    ];
    const STATUS_ORDER_PENDING = 'pending';
    const STATUS_ORDER_PROCESSING = 'processing';
    const STATUS_ORDER_SHIPPED = 'shipped';
    const STATUS_ORDER_SHIPPING = 'shipping';
    const STATUS_ORDER_DELIVERED = 'delivered';
    const STATUS_ORDER_CANCELLED = 'cancelled';
    const STARTUS_PAYMENT_UNPAID = 'unpaid';
    const STARTUS_PAYMENT_PAID = 'paid';
    const STARTUS_PAYMENT_FAILED = 'failed';
}
