<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCompleteOrders extends Command
{
    protected $signature = 'orders:complete';

    protected $description = 'Tự động chuyển trạng thái đơn hàng sang "Hoàn thành" sau 3 ngày nếu người dùng không xác nhận "Đã nhận hàng"';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $orders = Order::where('order_status', 'delivered')
            ->where('delivered_at', '<=', Carbon::now()->subDays(3))
            ->get();

        foreach ($orders as $order) {
            $order->order_status = 'completed';
            $order->save();
            $this->info('Order ID ' . $order->id . ' has been marked as completed.');
        }

        $this->info('Tất cả đơn hàng đủ điều kiện đã được chuyển sang trạng thái "Hoàn thành".');
        return 0;
    }
}
