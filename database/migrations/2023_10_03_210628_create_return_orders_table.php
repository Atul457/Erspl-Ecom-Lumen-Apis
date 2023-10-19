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
        Schema::create('tbl_return_order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deliveryboy_id')->nullable();
            $table->integer('accept_status')->default(0);
            $table->dateTime('assign_time')->nullable();
            $table->dateTime('accept_time')->nullable();
            $table->dateTime('picked_time')->nullable();
            $table->dateTime('reached_time')->nullable();
            $table->dateTime('delivered_time')->nullable();
            $table->string('otp', 11)->nullable();
            $table->date('return_date')->nullable();
            $table->integer('return_id')->nullable();
            $table->integer('order_reference')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_barcode', 50)->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('weight', 50)->nullable();
            $table->integer('qty')->default(0);
            $table->string('price', 11)->nullable();
            $table->string('mrp', 11)->nullable();
            $table->string('total', 11)->nullable();
            $table->string('return_total', 30)->nullable();
            $table->integer('status')->default(0);
            $table->integer('edit_status')->default(0);
            $table->string('shop_discount', 11)->default('0');
            $table->string('offer_total', 11)->default('0');
            $table->integer('return_status')->default(0);
            $table->integer('return_reason_id')->nullable();
            $table->string('return_remark', 255)->nullable();
            $table->float('pickup_distance', 10, 2)->default(0.00);
            $table->float('delivery_distance', 10, 2)->default(0.00);
            $table->integer('return_cancel_id')->nullable();
            $table->string('return_cancel_remark', 255)->nullable();
            $table->dateTime('return_cancel_date')->nullable();
            $table->dateTime('datetime')->nullable();

            $table->foreign("deliveryboy_id")->references("id")->on("tbl_employee");
            $table->foreign("shop_id")->references("id")->on("tbl_shop");
            $table->foreign('customer_id')->references('id')->on('tbl_registration');
            $table->foreign("product_id")->references("id")->on("tbl_product");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_return_order');
    }
};
