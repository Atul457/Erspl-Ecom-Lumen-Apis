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
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('unique_code')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('company')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('name')->nullable();
            $table->longText('slug_url')->nullable();
            $table->text('keywords')->nullable();
            $table->string('image')->nullable();
            $table->string('back_image')->nullable();
            $table->string('ingredient_image')->nullable();
            $table->string('nutrition_image')->nullable();
            $table->string('fssai_image')->nullable();
            $table->longText('small_description')->nullable();
            $table->string('fssai_no')->nullable();
            $table->string('barcode')->nullable();
            $table->string('hsn_code')->nullable();
            $table->integer('gst_rate')->nullable();
            $table->integer('cess_rate')->nullable();
            $table->string('customercare_details')->nullable();
            $table->string('disclaimer')->nullable();
            $table->string('origin_country')->nullable();
            $table->string('offer_details')->nullable();
            $table->string('dietary_needs')->nullable();
            $table->integer('sku_priority')->nullable();
            $table->integer('brand_priority')->nullable();
            $table->string('self_life')->nullable();
            $table->string('delivery_charge')->nullable()->default(0);
            $table->integer('returnable')->nullable()->default(1);
            $table->string('status')->nullable()->default("1");
            $table->string('verify_status')->nullable()->default("0")->comment("0=master 1=shop");
            $table->string('trend')->nullable();
            $table->string('special_type')->nullable()->default(0);
            $table->integer('sort_order')->nullable();
            $table->integer('priority_status')->default(0);
            $table->string('food_type')->nullable();
            $table->longText('marketed_by')->nullable();
            $table->string('basic_weight')->nullable()->comment("in gram for cart max weight calculation");
            $table->string('weight')->nullable();
            $table->integer('unit_id')->nullable();
            $table->integer('weight_in_gram')->nullable();
            $table->float('price')->nullable();
            $table->float('sellingprice')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign("subcategory_id")->references("id")->on("scategory");
            $table->foreign("category_id")->references("id")->on("acategory");
            $table->foreign("brand_id")->references("id")->on("brand");
            $table->foreign("shop_id")->references("id")->on("shop");
            $table->foreign("product_id")->references("id")->on("product");

            Schema::enableForeignKeyConstraints();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('product');
        Schema::enableForeignKeyConstraints();
    }
};
