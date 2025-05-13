<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_units', function (Blueprint $table) {
            $table->id();
            $table->string("sku")->unique();
            $table->string("condition");
            $table->text("notes")->nullable();
            $table->string("acquisition_source");
            $table->date("acquisition_date");
            $table->text("acquisition_notes")->nullable();
            $table->enum("status", ["available","borrowed","unknown","unavailable"])->default("available");
            $table->integer("quantity")->default(1); // misal item parent punya type consumable, maka bisa lebih dari 1, contoh: quantity dari 1 box pensil
            $table->string("qr_image_url");
            $table->foreignId("item_id")->constrained("items")->cascadeOnDelete();
            $table->foreignId("warehouse_id")->constrained("warehouses")->cascadeOnDelete();
            $table->string("current_location")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};
