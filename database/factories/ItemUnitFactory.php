<?php

namespace Database\Factories;

use App\Custom\Formatter;
use App\Models\Item;
use App\Models\Warehouse;
use BaconQrCode\Renderer\GDLibRenderer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class ItemUnitFactory extends Factory
{
    public function definition(): array
    {
        $item = Item::query()->inRandomOrder()->first();
        $slug = Formatter::makeDash($item->name . " unit " . $this->faker->unique()->slug);
        $quantity = $item->type === "non-consumable" ? 1 : $this->faker->numberBetween(1, 12);

        if (!Storage::disk('public')->exists('qr-images')) {
            Storage::disk('public')->makeDirectory('qr-images');
        }

        $renderer = new GDLibRenderer(400);
        $qrCode = new \BaconQrCode\Writer($renderer);

        $qrCodePath = 'qr-images/' . $slug . '.png';

        $qrValue = url('/api/admin/itemUnits/' . $slug);
        $qrCode->writeFile($qrValue, storage_path('app/public/' . $qrCodePath));

//        $qr_image_url = url(Storage::url($qrCodePath));
        $qr_image_url = env('APP_URL_X') . Storage::url($qrCodePath);

        return [
            'sku' => $slug,
            'condition' => $this->faker->word,
            'notes' => $this->faker->text,
            'acquisition_source' => $this->faker->company,
            'acquisition_date' => $this->faker->date,
            'acquisition_notes' => $this->faker->text,
            'status' => $this->faker->randomElement(['available', 'borrowed', 'unknown']),
            'quantity' => $quantity,
            'qr_image_url' => $qr_image_url,
            'item_id' => $item->id,
            'warehouse_id' => Warehouse::query()->select('id')->pluck('id')->random(),
        ];
    }
}
