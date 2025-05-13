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
        Schema::create('borrow_details', function (Blueprint $table) {
            $table->id();
            $table->integer("quantity")->default(1);
            $table->foreignId("borrow_request_id")->constrained("borrow_requests")->cascadeOnDelete();
            $table->foreignId("item_unit_id")->constrained("item_units")->cascadeOnDelete();
            $table->string("borrow_location");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_details');
    }
};
