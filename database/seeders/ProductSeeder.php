<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        ProductVariant::query()->truncate();
        DB::table('product_tags')->truncate();
        Product::query()->truncate();
        ProductSize::query()->truncate();
        ProductColor::query()->truncate();
        Tag::query()->truncate();

        Tag::factory(15)->create();

//        seed size
        $clothingSizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $shoeSizes = [39, 40, 41, 42, 43];

        foreach (['1', '2'] as $type) {
            if ($type === '1') {
                foreach ($clothingSizes as $size) {
                    ProductSize::query()->create([
                        'name' => $size,
                        'type' => $type, // Loại quần áo
                    ]);
                }
            } else {
                foreach ($shoeSizes as $size) {
                    ProductSize::query()->create([
                        'name' => $size,
                        'type' => $type, // Loại giày
                    ]);
                }
            }
        }


//        seed color
        foreach (['#FFFFFF', '#000000', '#FF0000', '#00FF00', '#0000FF', '#FFFF00'] as $color) {

            ProductColor::query()->create([

                'name' => $color
            ]);
        }

//        seed product
        for ($i = 0; $i < 100; $i++) {
            $priceRegular = fake()->numberBetween(100000, 1000000);

            // Tính tỷ lệ giảm giá ngẫu nhiên từ 10% đến 20%
            $discountRate = fake()->numberBetween(10, 20) / 100;

            // Tạo giá trị cho price_sale dựa trên tỷ lệ giảm giá
            $priceSale = $priceRegular * (1 - $discountRate);

            $name = fake()->text(100);

            Product::query()->create([
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::random(8),
                'sku' => Str::random(7) . $i,
                'img_thumbnail' => 'https://canifa.com/img/1000/1500/resize/8/b/8bj24s003-sj859-31-1-u.webp',
                'price_regular' => $priceRegular,
                'price_sale' => round($priceSale),
                'description' => fake()->text(255),
                'content' => Str::limit(fake()->paragraph(5), 255),
                'user_manual' => fake()->text(255),
                'view' => 0,
                'is_active' => 1,
                'is_new' => 1,
                'is_show_home' => 1,
                'category_id' => rand(1, 10),
                'brand_id' => rand(1, 10),

            ]);
        }

//        seed variant  tag
        for ($i = 0; $i < 101; $i++) {
            DB::table('product_tags')->insert([

                [
                    'product_id' => $i,
                    'tag_id' => rand(1, 8)],

                [
                    'product_id' => $i,
                    'tag_id' => rand(9, 15)],

            ]);

        }
//seed variant size color
        for ($productID = 1; $productID < 101; $productID++) {
            $data = [];
            for ($sizeID = 1; $sizeID < 6; $sizeID++) {
                for ($colorID = 1; $colorID < 7; $colorID++) {
                    $data = [
                        'product_id' => $productID,
                        'product_size_id' => $sizeID,
                        'product_color_id' => $colorID,
                        'quantity' => fake()->numberBetween(1, 20),
                        'image' => 'https://canifa.com/img/486/733/resize/8/b/8bs23s015-sk010-xl-1.webp',
                    ];
                }
            }
            DB::table('product_variants')->insert($data);
        }

    }

}
