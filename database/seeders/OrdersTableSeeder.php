<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrdersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = [
            [
                'user_id' => 1,
                'total_amount' => 175.00,
                'address_id' => 1,
                'payment_method' => 'credit_card',
                'payment_status' => Order::STATUS_PAYMENT_UNPAID,
                'order_status' => Order::STATUS_ORDER_PENDING,
                'note' => 'Giao hàng nhanh',
            ],
            [
                'user_id' => 2,
                'total_amount' => 75.00,
                'address_id' => 2,
                'payment_method' => 'paypal',
                'payment_status' => Order::STATUS_PAYMENT_UNPAID,
                'order_status' => Order::STATUS_ORDER_PENDING,
                'note' => 'Thay đổi địa chỉ giao hàng',
            ],

        ];


        DB::table('orders')->insert($orders);
    }
}
