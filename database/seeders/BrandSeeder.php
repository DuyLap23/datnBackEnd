<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 10; $i++) {
            \App\Models\Brand::query()->create([
                'name' => 'Brand ' . $i,
                'image' => 'https://picsum.photos/200/300?random=' . $i,
                'description' => fake()->paragraph(5),
            ]);
        }
    }
}
