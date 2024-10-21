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
        $orderItems = [
                [
                    'order_id' => 1,
                    'product_id' => 1,
                    'quantity' => 2,
                    'price' => 50.00,
                    'size' => 'M',
                    'color' => '#000000',
                ],
                [
                    'order_id' => 1,
                    'product_id' => 2,
                    'quantity' => 1,
                    'price' => 75.00,
                    'size' => 'L',
                    'color' => '#000000',
                ],
                [
                    'order_id' => 2,
                    'product_id' => 3,
                    'quantity' => 3,
                    'price' => 25.00,
                    'size' => 'S',
                    'color' => '#030303',
                ],

        ];


        DB::table('order_items')->insert($orderItems);
    }
}
