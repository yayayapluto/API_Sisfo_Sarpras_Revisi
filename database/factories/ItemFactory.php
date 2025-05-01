<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => fake()->unique()->name(),
            "type" => fake()->randomElement(["consumable","non-consumable"]),
            "description" => fake()->paragraph(),
            "image_url" => "placeholder",
            "category_id" => Category::query()->pluck("id")->random()
        ];
    }
}
