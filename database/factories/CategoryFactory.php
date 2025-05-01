<?php

namespace Database\Factories;

use App\Custom\Formatter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = "category " . fake()->unique()->words(fake()->numberBetween(1, 3), true);
        return [
            "slug" => Formatter::makeDash($name),
            "name" => $name,
            "description" => fake()->paragraph()
        ];
    }
}
