<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // Dữ liệu mẫu cho bảng order_items
       $orderItems = [
        [
            'order_id' => 1,
            'product_id' => 1,
            'quantity' => 2,
            'price' => 50.00,
            'size' => 'M',
            'color' => 'Red',
        ],
        [
            'order_id' => 1,
            'product_id' => 2,
            'quantity' => 1,
            'price' => 75.00,
            'size' => 'L',
            'color' => 'Blue',
        ],
        [
            'order_id' => 2,
            'product_id' => 3,
            'quantity' => 3,
            'price' => 25.00,
            'size' => 'S',
            'color' => 'Green',
        ],
        // Thêm nhiều dữ liệu khác nếu cần
    ];

    // Chèn dữ liệu vào bảng order_items
    DB::table('order_items')->insert($orderItems);
    }
}
