<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('status');
            $table->string('category_code');
            $table->integer('category_order');
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->string('photo')->nullable();
            $table->string('catlog')->nullable();
            $table->string('slider')->default("");
            $table->string('slug_url')->default("");
            $table->string('meta_title')->default("");
            $table->string('offer_title')->nullable();
            $table->unsignedBigInteger('industries_id');
            $table->string('meta_keywords')->default("");
            $table->string('meta_description')->default("");
            $table->timestamps();

            // Define foreign key relationship
            $table->foreign('industries_id')->references('id')->on('industries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet');
    }
};
