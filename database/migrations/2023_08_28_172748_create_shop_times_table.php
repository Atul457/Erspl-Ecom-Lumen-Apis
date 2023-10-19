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
        Schema::create('tbl_shop_time', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('day', 255)->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->integer('status')->nullable();
            $table->string('day_order', 11)->nullable();

            $table->foreign("shop_id")->references("id")->on("tbl_shop");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_shop_time');
    }
};
