<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerMktSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('banner_mkts')->insert([
            [
                'name' => 'Banner 1',
                'image' => 'banner1.jpg',
                'link' => 'http://example.com/banner1',
                'status' => 1,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(30),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Banner 2',
                'image' => 'banner2.jpg',
                'link' => 'http://example.com/banner2',
                'status' => 1,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(30),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Banner 3',
                'image' => 'banner3.jpg',
                'link' => 'http://example.com/banner3',
                'status' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(15),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Banner 4',
                'image' => 'banner4.jpg',
                'link' => 'http://example.com/banner4',
                'status' => 1,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(45),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Banner 5',
                'image' => 'banner5.jpg',
                'link' => 'http://example.com/banner5',
                'status' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(10),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
