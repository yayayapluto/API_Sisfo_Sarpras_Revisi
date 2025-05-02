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
        Schema::create('log_activities', function (Blueprint $table) {
            $table->id();
            $table->string("entity");
            $table->integer("entity_id");
            $table->enum("type", ["create","update","delete"]);
            $table->json("old_value")->nullable();
            $table->json("new_value")->nullable();
            $table->ipAddress();
            $table->string("user_agent");
            $table->foreignId("performed_by")->nullable()->constrained("users")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_activities');
    }
};
