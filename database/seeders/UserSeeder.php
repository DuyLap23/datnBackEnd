<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 5; $i++) {
            User::query()->create([
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'password' => Hash::make('password'),
                'link_fb' => fake()->url(),
                'link_tt' => fake()->url(),
                'role' => ['admin', 'staff','customer'][rand(0, 2)],
            ]);
        }
    }
}
