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
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon');
            $table->integer('status');
            $table->integer('category_order');
            $table->string('image')->nullable();
            $table->string('photo')->nullable();
            $table->string('catlog')->nullable();
            $table->string('slug_url')->default("");
            $table->string('meta_title')->default("");
            $table->string('offer_title')->default("");
            $table->string('meta_keywords')->default("");
            $table->string('meta_description')->default("");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('industries');
    }
};
