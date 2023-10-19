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
        Schema::create('tbl_rating', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->integer('delivery_boy_id')->nullable();
            $table->string('delivery_boy_rating', 11)->nullable();
            $table->string('rating', 11)->default('0');
            $table->string('review', 255)->nullable();
            $table->string('delivery_boy_review', 255)->nullable();
            $table->datetime('date')->nullable();
            
            $table->foreign("shop_id")->references("id")->on("tbl_shop");
            $table->foreign("user_id")->references("id")->on("tbl_registration");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_rating');
    }
};
