<?php

namespace Database\Factories;

use App\Custom\Formatter;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemUnitFactory extends Factory
{
    public function definition(): array
    {
        $item = Item::query()->inRandomOrder()->first();
        $slug = Formatter::makeDash($item->name . " unit " . $this->faker->unique()->slug);
        $quantity = $item->type === "non-consumable" ? 1 : $this->faker->numberBetween(1, 12);
        return [
            'slug' => $slug,
            'condition' => $this->faker->word,
            'notes' => $this->faker->text,
            'acquisition_source' => $this->faker->company,
            'acquisition_date' => $this->faker->date,
            'acquisition_notes' => $this->faker->text,
            'status' => $this->faker->randomElement(['available', 'borrowed', 'unknown']),
            'quantity' => $quantity,
            'qr_image_url' => $this->faker->imageUrl,
            'item_id' => $item->id,
            'warehouse_id' => Warehouse::query()->select('id')->pluck('id')->random(),
        ];
    }
}
