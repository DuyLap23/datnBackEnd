<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSampleProductsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')->insert([
            [
                'id' => 1,
                'name' => 'Sản phẩm 1',
                'slug' => 'san-pham-1',
                'sku' => 'SKU001',
                'img_thumbnail' => 'link-to-image-1.jpg',
                'price_regular' => 100000,
                'price_sale' => 90000,
                'description' => 'Mô tả sản phẩm 1',
                'content' => 'Nội dung sản phẩm 1',
                'user_manual' => 'Hướng dẫn sử dụng sản phẩm 1',
                'view' => 10,
                'is_active' => 1,
                'is_new' => 1,
                'is_show_home' => 1,
                'category_id' => 1,
                'brand_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null
            ],
            [
                'id' => 2,
                'name' => 'Sản phẩm 2',
                'slug' => 'san-pham-2',
                'sku' => 'SKU002',
                'img_thumbnail' => 'link-to-image-2.jpg',
                'price_regular' => 200000,
                'price_sale' => 180000,
                'description' => 'Mô tả sản phẩm 2',
                'content' => 'Nội dung sản phẩm 2',
                'user_manual' => 'Hướng dẫn sử dụng sản phẩm 2',
                'view' => 20,
                'is_active' => 1,
                'is_new' => 1,
                'is_show_home' => 1,
                'category_id' => 1,
                'brand_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null
            ],
            // Thêm các sản phẩm khác tương tự
        ]);
    }        

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
    }
}
