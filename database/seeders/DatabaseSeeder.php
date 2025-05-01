<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->truncate();
        User::query()->create([
            'username' => "admin",
            'password' => Hash::make("admin123"),
            'role' => "admin",
        ]);

        Category::query()->truncate();
        Category::factory(fake()->numberBetween(10, 20))->create();

        Warehouse::query()->truncate();
        Warehouse::factory(fake()->numberBetween(3, 5))->create();
    }
}
