<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VouCher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {
            // Tạo giá trị cho discount_type
            $discount_type = fake()->randomElement(['fixed', 'percent']);

            // Tạo discount_value dựa trên discount_type
            if ($discount_type === 'percent') {
                $discount_value = fake()->numberBetween(1, 100); // Giảm giá theo phần trăm (1% đến 100%)
            } else {
                $discount_value = fake()->numberBetween(100000, 200000); // Giảm giá theo số tiền cố định
            }

            VouCher::query()->create([
                'name' => fake()->name(),
                'minimum_order_value' => fake()->numberBetween(100000, 200000),
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'start_date' => fake()->dateTime(),
                'end_date' => fake()->dateTime(),
            ]);
        }
    }

}
