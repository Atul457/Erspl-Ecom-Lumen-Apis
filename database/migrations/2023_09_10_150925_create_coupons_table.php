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
        Schema::create('tbl_coupon', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->text('coupon_title');
            $table->text('coupon_subtitle');
            $table->integer('status')->nullable()->comment('0 = Inactive, 1 = Active');
            $table->string('couponcode', 255)->nullable();
            $table->datetime('start_date')->nullable();
            $table->datetime('expire_date')->nullable();
            $table->integer('times_used_coupon')->nullable()->comment('0=UNLIMITED 1=LIMITED');
            $table->integer('time_to_use_coupon')->nullable();
            $table->integer('times_used')->nullable()->comment('0 = UNLIMITED, 1 = LIMITED');
            $table->integer('time_to_use')->nullable();
            $table->float('discount', 10, 2)->nullable();
            $table->integer('discount_type')->nullable()->comment('0 = PERCENT, 1 = RUPEES');
            $table->string('minimum_value', 50)->nullable();
            $table->string('discount_upto', 11)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('apply_type')->nullable()->comment('0 = ANY ITEMS, 1 = SELECTED ITEM');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('subcategory_id', 255)->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->datetime('created_date');

            $table->foreign('user_id')->references('id')->on('tbl_registration');
            $table->foreign('category_id')->references('id')->on('tbl_acategory');
            $table->foreign('product_id')->references('id')->on('tbl_product');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_coupon');
    }
};
