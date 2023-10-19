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
        Schema::create('tbl_order_edited', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->string('deliveryboy_id', 10)->nullable();
            $table->string('return_deliveryboy_id', 10)->nullable();
            $table->string('payment_received_type', 15)->nullable();
            $table->string('otp', 255)->nullable();
            $table->integer('accept_status')->default(0);
            $table->datetime('approved_date')->nullable();
            $table->datetime('assign_date')->nullable();
            $table->datetime('accept_date')->nullable();
            $table->datetime('picked_date')->nullable();
            $table->datetime('reach_date')->nullable();
            $table->datetime('delivered_date')->nullable();
            $table->integer('reach_status')->default(0);
            $table->string('order_type', 255)->default('online');
            $table->date('order_date')->nullable();
            $table->integer('order_reference')->nullable();
            $table->integer('order_id')->nullable();
            $table->datetime('expected_delivered_date')->nullable();
            $table->datetime('packed_date')->nullable();
            $table->datetime('dispatch_date')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('shop_city_id')->nullable();
            $table->string('product_barcode', 255)->nullable();
            $table->text('product_name');
            $table->string('weight', 255)->nullable();
            $table->float('price')->nullable();
            $table->float('mrp')->nullable();
            $table->float('basic_price', 10, 2)->nullable();
            $table->integer('qty')->nullable();
            $table->float('total')->nullable();
            $table->string('hsn_code', 255)->nullable();
            $table->string('tax_rate', 50)->nullable();
            $table->string('cess_rate', 50)->nullable();
            $table->integer('delivery_type')->nullable()->comment('1=pickup 2=sloted 3=express');
            $table->float('delivery_charge')->nullable();
            $table->string('shop_total', 50)->nullable();
            $table->string('shop_actual_total', 10)->nullable();
            $table->float('order_total')->nullable();
            $table->string('delivery_image', 255)->nullable();
            $table->integer('status')->nullable()->comment('0=placed 1=accepted 2=dispatched 3=delivered 4=failed 5=canceled 6=rejected 7=return');
            $table->integer('edit_status')->default(0);
            $table->integer('edit_confirm')->default(0);
            $table->integer('cancel_status')->nullable()->comment('1=user 2=shop 3=deliveryBoy');
            $table->integer('reason_id')->nullable();
            $table->string('cancel_remark', 255)->nullable();
            $table->datetime('cancel_date')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('mobile', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('pancard_no', 255)->nullable();
            $table->string('gst_no', 255)->nullable();
            $table->text('flat');
            $table->text('landmark');
            $table->string('pincode', 11)->nullable();
            $table->text('address');
            $table->string('city', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->text('notes');
            $table->text('remark');
            $table->string('address_type', 255)->nullable();
            $table->string('payment_type', 255)->nullable();
            $table->string('payment_status', 255)->nullable();
            $table->string('paytm_order_id', 255)->nullable();
            $table->float('paytm_rate', 10, 2)->nullable();
            $table->text('paytm_txn_id');
            $table->text('payment_mode');
            $table->datetime('payment_txn_date')->nullable();
            $table->integer('denial_status')->default(0)->comment('0= processing 1=completed');
            $table->integer('denial_reason_id')->nullable();
            $table->string('denial_remark', 255)->nullable();
            $table->datetime('denial_date')->nullable();
            $table->string('denial_amount', 255)->nullable();
            $table->integer('return_status')->default(0)->comment('0=pending 1=accepted/processing 2=rejected 3=verified');
            $table->datetime('return_date')->nullable();
            $table->datetime('return_pickup_time')->nullable();
            $table->datetime('return_complete_time')->nullable();
            $table->integer('return_reason_id')->nullable();
            $table->string('return_remark', 255)->nullable();
            $table->datetime('return_delivered_date')->nullable();
            $table->integer('refund_status')->default(0);
            $table->string('refund_amount', 50)->nullable();
            $table->text('refund_id');
            $table->datetime('refund_date')->nullable();
            $table->text('refund_refid');
            $table->text('refund_txnid');
            $table->text('refund_txntime');
            $table->string('latitude', 255)->nullable();
            $table->string('longitude', 255)->nullable();
            $table->string('travel_distance', 255)->default('0');
            $table->string('travel_time', 255)->nullable();
            $table->float('pickup_distance', 10, 1)->default(0.0);
            $table->float('delivery_distance', 10, 1)->default(0.0);
            $table->integer('deliveryboy_settle')->default(0);
            $table->integer('offer_type')->nullable()->comment('0=normal 1=sku 2=price');
            $table->string('offer_id', 11)->nullable();
            $table->string('offer_price', 11)->nullable();
            $table->string('offer_discount', 11)->nullable();
            $table->string('offer_primary_id', 11)->nullable();
            $table->string('offer_primary_qty', 11)->nullable();
            $table->string('offer_total', 11)->nullable();
            $table->string('coupon', 255)->nullable();
            $table->string('shop_discount', 50)->nullable();
            $table->string('coupon_discount', 10)->nullable();
            $table->string('referral_bonus', 10)->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('cancelled_by')->nullable();
            $table->integer('assigned_by')->nullable();
            $table->datetime('date')->nullable();

            $table->foreign('customer_id')->references('id')->on('tbl_registration');
            $table->foreign('product_id')->references('id')->on('tbl_product');
            $table->foreign('shop_city_id')->references('id')->on('tbl_city');
            $table->foreign('shop_id')->references('id')->on('tbl_shop');

        });
        Schema::disableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::enableForeignKeyConstraintsForeignKeyConstraints();
        Schema::dropIfExists('tbl_order_edited');
        Schema::disableForeignKeyConstraints();
    }
};
