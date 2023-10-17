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
        Schema::enableForeignKeyConstraints();
        Schema::create('order_edited', function (Blueprint $table) {
            $table->id();
            $table->string("deliveryboy_id")->nullable();
            $table->string("return_deliveryboy_id")->nullable();
            $table->string("payment_received_type")->nullable();
            $table->string("otp")->nullable();
            $table->integer("accept_status")->nullable()->default(0);
            $table->dateTime("approved_date")->nullable();
            $table->dateTime("assign_date")->nullable();
            $table->dateTime("accept_date")->nullable();
            $table->dateTime("picked_date")->nullable();
            $table->dateTime("reach_date")->nullable();
            $table->dateTime("delivered_date")->nullable();
            $table->integer("reach_status")->nullable()->default(0);
            $table->string("order_type")->nullable()->default("online");
            $table->dateTime("order_date")->nullable();
            $table->integer("order_reference")->nullable();
            $table->integer("order_id")->nullable();
            $table->dateTime("expected_delivered_date")->nullable();
            $table->dateTime("packed_date")->nullable();
            $table->dateTime("dispatch_date")->nullable();
            $table->unsignedBigInteger("customer_id")->nullable();
            $table->unsignedBigInteger("product_id")->nullable();
            $table->unsignedBigInteger("shop_id")->nullable();
            $table->unsignedBigInteger("shop_city_id")->nullable();
            $table->string("product_barcode")->nullable();
            $table->string("product_name")->nullable()->default("");
            $table->string("weight")->nullable();
            $table->float("price")->nullable();
            $table->float("mrp")->nullable();
            $table->float("basic_price")->nullable();
            $table->integer("qty")->nullable();
            $table->float("total")->nullable();
            $table->string("hsn_code")->nullable();
            $table->string("tax_rate")->nullable();
            $table->string("cess_rate")->nullable();
            $table->integer("delivery_type")->nullable()->comment("1=pickup 2=sloted 3=express");
            $table->float("delivery_charge")->nullable();
            $table->string("shop_total")->nullable();
            $table->string("shop_actual_total")->nullable();
            $table->float("order_total")->nullable();
            $table->string("delivery_image")->nullable();
            $table->integer("status")->nullable()->comment("0=placed 1=accepted 2=dispatched 3=delivered 4=failed 5=canceled 6=rejected 7=return");
            $table->integer("edit_status")->nullable()->default(0);
            $table->integer("edit_confirm")->nullable()->default(0);
            $table->integer("cancel_status")->nullable()->comment("1=user 2=shop 3=deliveryBoy");
            $table->integer("reason_id")->nullable();
            $table->string("cancel_remark")->nullable();
            $table->dateTime("cancel_date")->nullable();
            $table->string("name")->nullable();
            $table->string("mobile")->nullable();
            $table->string("email")->nullable();
            $table->string("pancard_no")->nullable();
            $table->string("gst_no")->nullable();
            $table->text("flat")->nullable();
            $table->text("landmark")->nullable();
            $table->string("pincode")->nullable();
            $table->text("address")->nullable();
            $table->string("city")->nullable();
            $table->string("state")->nullable();
            $table->text("notes")->nullable()->default("");
            $table->text("remark")->nullable()->default("");
            $table->string("address_type")->nullable();
            $table->string("payment_type")->nullable();
            $table->string("payment_status")->nullable();
            $table->string("paytm_order_id")->nullable();
            $table->float("paytm_rate")->nullable();
            $table->text("paytm_txn_id")->nullable();
            $table->text("payment_mode")->nullable();
            $table->dateTime("payment_txn_date")->nullable();
            $table->integer("denial_status")->default(0)->comment("0= processing 1=completed");
            $table->integer("denial_reason_id")->nullable();
            $table->string("denial_remark")->nullable();
            $table->dateTime("denial_date")->nullable();
            $table->string("denial_amount")->nullable();
            $table->integer("return_status")->nullable()->default(0)->comment("0=pending 1=accepted/processing 2=rejected 3=verified 4=picked");
            $table->dateTime("return_date")->nullable();
            $table->dateTime("return_pickup_time")->nullable();
            $table->dateTime("return_complete_time")->nullable();
            $table->integer("return_reason_id")->nullable();
            $table->string("return_remark")->nullable();
            $table->dateTime("return_delivered_date")->nullable();
            $table->integer("refund_status")->nullable()->default(0);
            $table->string("refund_amount")->nullable();
            $table->string("refund_id")->nullable();
            $table->dateTime("refund_date")->nullable();
            $table->text("refund_refid")->nullable();
            $table->text("refund_txnid")->nullable()->default("");
            $table->text("refund_remark")->nullable()->default("");
            // $table->integer("refund_by")->nullable();
            $table->dateTime("refund_txntime")->nullable();
            $table->string("latitude")->nullable();
            $table->string("longitude")->nullable();
            $table->integer("travel_distance")->nullable()->default(0);
            $table->string("travel_time")->nullable();
            $table->float("pickup_distance")->nullable()->default(0.0);
            $table->float("delivery_distance")->nullable()->default(0.0);
            $table->integer("deliveryboy_settle")->nullable()->default(0);
            $table->integer("offer_type")->nullable()->comment("0=normal 1=sku 2=price");
            $table->string("offer_id")->nullable();
            $table->string("offer_price")->nullable();
            $table->string("offer_discount")->nullable();
            $table->string("offer_primary_id")->nullable();
            $table->string("offer_primary_qty")->nullable();
            $table->string("offer_total")->nullable();
            $table->string("coupon")->nullable();
            $table->string("shop_discount")->nullable();
            $table->string("coupon_discount")->nullable();
            $table->string("referral_bonus")->nullable();
            $table->integer("added_by")->nullable();
            $table->integer("cancelled_by")->nullable();
            $table->integer("assigned_by")->nullable();
            $table->dateTime("date")->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('customer_id')->references('id')->on('tbl_registration');
            $table->foreign('product_id')->references('id')->on('product');
            $table->foreign('shop_city_id')->references('id')->on('tbl_city');
            $table->foreign('shop_id')->references('id')->on('shop');

        });
        Schema::disableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::enableForeignKeyConstraintsForeignKeyConstraints();
        Schema::dropIfExists('order_edited');
        Schema::disableForeignKeyConstraints();
    }
};
