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
        Schema::create('tbl_cart', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('weight', 255)->nullable();
            $table->integer('qty')->nullable();
            $table->integer('offer_type')->default(0)->comment('0=normal 1=sku 2=price');
            $table->integer('pricebase_offer_id')->nullable();
            $table->integer('offer_by')->nullable()->comment('1 = Offer by Shop, 2 = Offer by e-RSPL');

            $table->foreign("shop_id")->references("id")->on("tbl_shop");
            $table->foreign("user_id")->references("id")->on("tbl_registration");
            $table->foreign("product_id")->references("id")->on("tbl_product");
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_cart');
        Schema::enableForeignKeyConstraints();
    }
};
