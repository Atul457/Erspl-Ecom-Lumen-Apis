<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSliderProductsTable extends Migration
{
    public function up()
    {
        Schema::create('tbl_slider_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('slider_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('unique_code', 50)->nullable();
            $table->integer('sort_order')->nullable();

            $table->foreign("shop_id")->references("id")->on("tbl_shop");
            $table->foreign("slider_id")->references("id")->on("tbl_slider");
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_slider_products');
    }
}
