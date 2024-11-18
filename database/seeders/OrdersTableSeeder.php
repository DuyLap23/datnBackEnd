<?php

namespace Database\Seeders;

use App\Models\Order;
use Carbon\Carbon;
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
                'order_code' => strtoupper('ORD' . uniqid()),
                'user_id' => 1,
                'total_amount' => 175.00,
                'address_id' => 1,
                'payment_method' => 2,
                'payment_status' => Order::STATUS_PAYMENT_UNPAID,
                'order_status' => Order::STATUS_ORDER_PENDING,
                'note' => 'Giao hàng nhanh',
                'created_at' => Carbon::now(),  
                'updated_at' => Carbon::now(), 
            ],
            [
                'order_code' => strtoupper('ORD' . uniqid()),
                'user_id' => 2,
                'total_amount' => 75.00,
                'address_id' => 2,
                'payment_method' => 1,
                'payment_status' => Order::STATUS_PAYMENT_UNPAID,
                'order_status' => Order::STATUS_ORDER_PENDING,
                'note' => 'Thay đổi địa chỉ giao hàng',
                'created_at' => Carbon::now(), 
                'updated_at' => Carbon::now(),
            ],

        ];


        DB::table('orders')->insert($orders);
    }
}
