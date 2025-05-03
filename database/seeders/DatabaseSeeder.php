<?php

namespace Database\Seeders;

use App\Models\BorrowDetail;
use App\Models\BorrowRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\ReturnDetail;
use App\Models\ReturnRequest;
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
        BorrowDetail::query()->truncate();
        ReturnRequest::query()->truncate();
        BorrowRequest::factory(fake()->numberBetween(50, 100))->create();
        $borrowRequests = BorrowRequest::all()->shuffle();
        foreach ($borrowRequests as $borrowRequest) {
                $borrowDetailCount = rand(1, 10);
                for ($i = 0; $i < $borrowDetailCount; $i++) {
                    $itemUnit = ItemUnit::query()->get()->random();
                    $quantity = $itemUnit->type === "non-consumable" ? 1 : fake()->numberBetween(1, $itemUnit->quantity ?? 1);
                    BorrowDetail::query()->create([
                        "quantity" => $quantity,
                        "borrow_request_id" => $borrowRequest->id,
                        "item_unit_id" => $itemUnit->id
                    ]);
                }
            if ($borrowRequest->status === "approved") {
                $returnRequest = ReturnRequest::query()->create([
                    "notes" => fake()->paragraph(),
                    "borrow_request_id" => $borrowRequest->id
                ]);
                $returnRequestCount = $borrowRequest->returnRequest->sum;
                for ($j = 0; $j < $returnRequestCount; $j++) {
                    ReturnDetail::query()->create([
                        "condition" => "recent condition",
                        "item_unit_id" => ItemUnit::query()->inRandomOrder()->first()->id,
                        "return_request_id" => $returnRequest->id
                    ]);
                }
            }
        }

        DB::statement("SET FOREIGN_KEY_CHECKS = 1");
    }
}
