<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Category::query()->create([
                'name' => 'Category ' . $i,
                'image' => 'https://picsum.photos/200/300?random=' . $i,
                'slug' => 'category-' . $i,
                'parent_id' => null,
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            Category::query()->create([
                'name' => 'Category ' . $i,
                'image' => 'https://picsum.photos/200/300?random=' . $i,
                'slug' => 'category-' . $i,
                'parent_id' => rand(1, 5),
            ]);
        }
    }
}
