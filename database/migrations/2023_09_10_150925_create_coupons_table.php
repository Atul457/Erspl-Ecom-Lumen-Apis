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
        Schema::create('coupon', function (Blueprint $table) {
            $table->id();
            $table->text("coupon_title")->nullable()->default("");
            $table->text("coupon_subtitle")->nullable()->default("");
            $table->integer("status")->nullable()->comment("0 = Inactive, 1 = Active");
            $table->string("couponcode")->nullable();
            $table->dateTime("start_date")->nullable();
            $table->dateTime("expire_date")->nullable();
            $table->integer("times_used_coupon")->nullable()->comment("0=UNLIMITED 1=LIMITED");
            $table->integer("time_to_use_coupon")->nullable();
            $table->integer("times_used")->nullable()->comment("0=UNLIMITED 1=LIMITED");
            $table->integer("time_to_use")->nullable();
            $table->float("discount")->nullable();
            $table->integer("discount_type")->nullable()->comment("0 = PERSENT, 1 = RUPEES");
            $table->string("minimum_value")->nullable();
            $table->string("discount_upto")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->integer("apply_type")->nullable()->comment("0 = ANY ITEMS, 1 = SELECTED ITEM");
            $table->unsignedBigInteger("category_id")->nullable();
            $table->string("subcategory_id")->nullable();
            $table->unsignedBigInteger("product_id")->nullable();
            $table->dateTime("created_date")->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('acategory');
            $table->foreign('product_id')->references('id')->on('product');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon');
    }
};
