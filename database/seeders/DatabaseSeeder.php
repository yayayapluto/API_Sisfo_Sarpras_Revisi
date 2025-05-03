<?php

namespace Database\Seeders;

use App\Models\BorrowDetail;
use App\Models\BorrowRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement("SET FOREIGN_KEY_CHECKS = 0");

        User::query()->truncate();
        User::query()->create([
            'username' => "admin",
            'password' => Hash::make("admin123"),
            'role' => "admin",
        ]);
        User::query()->create([
            'username' => "user",
            'password' => Hash::make("user123"),
            'role' => "user",
        ]);

        Category::query()->truncate();
        Category::factory(fake()->numberBetween(10, 20))->create();

        Warehouse::query()->truncate();
        Warehouse::factory(fake()->numberBetween(3, 5))->create();

        Item::query()->truncate();
        Item::factory(fake()->numberBetween(10, 20))->create();

        ItemUnit::query()->truncate();
        ItemUnit::factory(100)->create();

        BorrowRequest::query()->truncate();
        BorrowRequest::factory(fake()->numberBetween(50, 100))->create();
        $borrowRequests = BorrowRequest::all()->shuffle();
        foreach ($borrowRequests as $borrowRequest) {
            $itemUnit = ItemUnit::query()->get()->random();
            $quantity = $itemUnit->type === "non-consumable" ? 1 : fake()->numberBetween(1, $itemUnit->quantity ?? 1);
            BorrowDetail::query()->create([
                "quantity" => $quantity,
                "borrow_request_id" => $borrowRequest->id,
                "item_unit_id" => $itemUnit->id
            ]);
        }

        DB::statement("SET FOREIGN_KEY_CHECKS = 1");
    }
}
