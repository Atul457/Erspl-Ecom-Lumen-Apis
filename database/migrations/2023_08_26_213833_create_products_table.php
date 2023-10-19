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
        Schema::disableForeignKeyConstraints();
        Schema::create('tbl_product', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('unique_code', 50)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->text('company');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('name', 255)->nullable();
            $table->text('slug_url');
            $table->text('keywords');
            $table->string('image', 255)->nullable();
            $table->string('back_image', 255)->nullable();
            $table->string('ingredient_image', 255)->nullable();
            $table->string('nutrition_image', 255)->nullable();
            $table->string('fssai_image', 255)->nullable();
            $table->text('small_description');
            $table->string('fssai_no', 255)->nullable();
            $table->string('barcode', 255)->nullable();
            $table->string('hsn_code', 50)->nullable();
            $table->integer('gst_rate')->nullable();
            $table->integer('cess_rate')->nullable();
            $table->string('customercare_details', 255)->nullable();
            $table->string('disclaimer', 255)->nullable();
            $table->string('origin_country', 255)->nullable();
            $table->string('offer_details', 255)->nullable();
            $table->string('dietary_needs', 255)->nullable();
            $table->integer('sku_priority')->nullable();
            $table->integer('brand_priority')->nullable();
            $table->string('self_life', 50)->nullable();
            $table->string('delivery_charge', 11)->default('0');
            $table->integer('returnable')->default(1);
            $table->string('status', 5)->default('1');
            $table->string('verify_status', 5)->default('0')->comment('0=master 1=shop');
            $table->string('trend', 5)->nullable();
            $table->integer('banner_id')->nullable();
            $table->integer('banner_sort')->nullable();
            $table->string('special_type', 11)->default('0');
            $table->integer('sort_order')->nullable();
            $table->integer('priority_status')->default(0);
            $table->string('food_type', 10)->nullable();
            $table->text('marketed_by');
            $table->string('basic_weight', 50)->comment('in gram for cart max weight calculation')->nullable();
            $table->string('weight', 50)->nullable();
            $table->integer('unit_id')->nullable();
            $table->float('weight_in_gram', 10, 2)->nullable();
            $table->float('price', 10, 2)->nullable();
            $table->float('sellingprice', 10, 2)->nullable();

            $table->foreign("subcategory_id")->references("id")->on("tbl_scategory");
            $table->foreign("category_id")->references("id")->on("tbl_acategory");
            $table->foreign("brand_id")->references("id")->on("tbl_brand");
            $table->foreign("shop_id")->references("id")->on("tbl_shop");
            $table->foreign("product_id")->references("id")->on("tbl_product");

            Schema::enableForeignKeyConstraints();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_product');
        Schema::enableForeignKeyConstraints();
    }
};
