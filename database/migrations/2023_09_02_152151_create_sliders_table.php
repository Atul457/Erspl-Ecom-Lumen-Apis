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
        Schema::create('tbl_slider', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->string('title', 255)->nullable();
            $table->string('sub_title', 255)->nullable();
            $table->string('slider', 255)->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('link', 255)->nullable();
            $table->integer('sort_order')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->datetime('datetime')->nullable();

            $table->foreign("shop_id")->references("id")->on("tbl_shop");
            $table->foreign("created_by")->references("id")->on("tbl_registration");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_slider');
        Schema::enableForeignKeyConstraints();
    }
};
