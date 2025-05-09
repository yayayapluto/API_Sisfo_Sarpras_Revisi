<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BorrowRequest>
 */
class BorrowRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(["pending","approved","rejected"]);
        $handled_by = $status !== "pending" ? User::query()->select("id")->inRandomOrder()->where("role","admin")->first() : null;
        return [
            "return_date_expected" => Carbon::now()->addDays(fake()->numberBetween(1, 3)),
            "status" => $status,
            "notes" => fake()->paragraph(),
            "handled_by" => $handled_by,
            "user_id" => User::query()->select("id")->inRandomOrder()->where("role","user")->first(),
        ];
    }
}
