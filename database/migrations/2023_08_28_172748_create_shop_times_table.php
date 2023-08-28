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
        Schema::create('shop_time', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("shop_id")->nullable();
            $table->string("day")->nullable();
            $table->time("time_from")->nullable();
            $table->time("time_to")->nullable();
            $table->integer("status")->nullable();
            $table->string("day_order")->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign("shop_id")->references("id")->on("shop");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_time');
    }
};
