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
        'order_code',
        'address_id',
        'payment_method',
        'payment_status',
        'order_status',
        'total_amount',
        'voucher_code',
        'voucher_discount',
        'note',
    ];
    protected $casts = [
        'total_amount' => 'float',
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
        'shipping' => 'Đang vận chuyển',
        'delivered' => 'Đã giao hàng',
        'received' => 'Đã nhận',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
        'failed' => 'Giao hàng thất bại',
        'rescheduled' => 'Đã lên lịch lại'
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
    const STATUS_ORDER_RECEIVED = 'received';
    const STATUS_ORDER_CANCELLED = 'cancelled';
    const STATUS_ORDER_COMLETTED = 'completed';
    const STATUS_PAYMENT_UNPAID = 'unpaid';
    const STATUS_PAYMENT_PAID = 'paid';
    const STATUS_PAYMENT_FAILED = 'failed';
}
