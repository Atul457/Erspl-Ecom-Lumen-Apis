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
        Schema::create('tbl_fav_shop', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("shop_id")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->dateTime("datetime")->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign("shop_id")->references("id")->on("shop");
            $table->foreign("user_id")->references("id")->on("tbl_registration");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_fav_shop');
    }
};
