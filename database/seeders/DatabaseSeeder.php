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
             'username' => "farras",
             'password' => Hash::make("farras123"),
             'role' => "user",
         ]);

        // User::query()->create([
        //     'username' => "user1",
        //     'password' => Hash::make("user123"),
        //     'role' => "user",
        // ]);

        Category::query()->truncate();
//         Category::factory(3)->create();

        Warehouse::query()->truncate();
//         Warehouse::factory(2)->create();

        Item::query()->truncate();
//         Item::factory(5)->create();

        ItemUnit::query()->truncate();
//         ItemUnit::factory(91)->create();

        BorrowRequest::query()->truncate();
        BorrowDetail::query()->truncate();
        ReturnRequest::query()->truncate();
        ReturnDetail::query()->truncate();
        // BorrowRequest::factory(10)->create();
        // $borrowRequests = BorrowRequest::all()->shuffle();
        // foreach ($borrowRequests as $borrowRequest) {
        //         $borrowDetailCount = rand(1, 10);
        //         for ($i = 0; $i < $borrowDetailCount; $i++) {
        //             $itemUnit = ItemUnit::query()->get()->random();
        //             $quantity = $itemUnit->type === "non-consumable" ? 1 : fake()->numberBetween(1, $itemUnit->quantity ?? 1);
        //             BorrowDetail::query()->create([
        //                 "quantity" => $quantity,
        //                 "borrow_request_id" => $borrowRequest->id,
        //                 "item_unit_id" => $itemUnit->id
        //             ]);
        //         }
        //     if ($borrowRequest->status === "approved") {
        //         $status = fake()->randomElement(["pending","approved","rejected"]);
        //         $handled_by = $status !== "pending" ? User::query()->select("id")->inRandomOrder()->where("role","admin")->first()->id : null;
        //         $returnRequest = ReturnRequest::query()->create([
        //             "notes" => fake()->paragraph(),
        //             "status" => $status,
        //             "handled_by" => $handled_by,
        //             "borrow_request_id" => $borrowRequest->id
        //         ]);
        //         $returnDetailCount = count($borrowRequest->borrowDetails);
        //         for ($j = 0; $j < $returnDetailCount; $j++) {
        //             ReturnDetail::query()->create([
        //                 "item_unit_id" => ItemUnit::query()->inRandomOrder()->first()->id,
        //                 "return_request_id" => $returnRequest->id
        //             ]);
        //         }
        //     }
        // }

        DB::statement("SET FOREIGN_KEY_CHECKS = 1");
    }
}
