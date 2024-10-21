<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        // Lấy tất cả sản phẩm để làm nguồn cho giỏ hàng
        $products = Product::all();

        foreach ($products as $product) {
            // Lấy một biến thể ngẫu nhiên cho sản phẩm
            $productVariant = ProductVariant::where('product_id', $product->id)
                ->inRandomOrder()
                ->first();

            if (!$productVariant) {
                Log::error("No product variant found for product ID: " . $product->id);
                continue; // Bỏ qua nếu không có biến thể
            }

            // Chọn một user ngẫu nhiên
            $userId = rand(1, 10); // Giả sử bạn có 10 user

            // Kiểm tra xem sản phẩm đã có trong giỏ hàng của user chưa
            $cartItem = Cart::query()->where('user_id', $userId)
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem) {
                // Nếu đã có, tăng số lượng
                $cartItem->quantity += rand(1, 5); // Tăng số lượng ngẫu nhiên từ 1 đến 5
                $cartItem->save();
            } else {
                // Nếu chưa có, tạo mới giỏ hàng
                Cart::query()->create([
                    'user_id' => $userId,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 10),
                    'price' => $product->price_regular ?: $productVariant->price_sale, // Lấy giá từ biến thể
                    'color' => $productVariant->productColor, // Lấy màu từ biến thể
                    'size' => $productVariant->productSize,
                ]);
            }
        }
    }

}
