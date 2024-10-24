<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes; // Đã sửa lỗi ở đây

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

    const STATUS_ORDER = [
        'pending' => 'Chờ xác nhận',
        'processing' => 'Đã xác nhận',
        'shipped' => 'Đang vận chuyển',
        'delivered' => 'Đã giao hàng',
        'cancelled' => 'Đã hủy'
    ];

    const STATUS_PAYMENT = [
        'unpaid' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại'
    ];

    // Các trạng thái đơn lẻ
    const STATUS_ORDER_PENDING = 'pending';
    const STATUS_ORDER_PROCESSING = 'processing';
    const STATUS_ORDER_SHIPPING = 'shipping';
    const STATUS_ORDER_DELIVERED = 'delivered';
    const STATUS_ORDER_CANCELLED = 'cancelled';
    const STATUS_PAYMENT_UNPAID = 'unpaid';
    const STATUS_PAYMENT_PAID = 'paid';
    const STATUS_PAYMENT_FAILED = 'failed';
}
