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
        Schema::create('tbl_home', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->text('title');
            $table->varchar('shop_range', 10)->nullable();
            $table->varchar('weight_capping', 10)->nullable();
            $table->varchar('order_capping', 10)->nullable();
            $table->varchar('shop_capping', 10)->nullable();
            $table->varchar('logo', 255);
            $table->varchar('version', 11)->nullable();
            $table->varchar('contact', 255);
            $table->varchar('email', 255);
            $table->text('address');
            $table->integer('delivery_type')->nullable()->comment('1=Free 2=Paid');
            $table->varchar('delivery_charge', 255)->nullable();
            $table->varchar('minimum_value', 11)->nullable();
            $table->longText('notification_key');
            $table->varchar('sms_vendor', 10)->nullable();
            $table->varchar('zap_key', 255)->nullable();
            $table->varchar('fortius_key', 255)->nullable();
            $table->varchar('packing_charge', 11)->nullable();
            $table->varchar('meta_keyword', 255);
            $table->varchar('meta_description', 255);
            $table->varchar('fav_icon', 255);
            $table->varchar('category1_id', 255);
            $table->varchar('gallery1', 255);
            $table->varchar('category2_id', 255);
            $table->varchar('gallery2', 255);
            $table->text('title1')->nullable();
            $table->varchar('count1', 10)->nullable();
            $table->text('title2')->nullable();
            $table->integer('category2')->nullable();
            $table->varchar('count2', 10)->nullable();
            $table->text('footer_description');
            $table->text('company_name');
            $table->varchar('gst', 255);
            $table->text('tInfo_temp');
            $table->integer('wallet_status')->default(0);
            $table->integer('prepaid_status')->default(0);
            $table->varchar('paytm_mid', 255)->nullable();
            $table->varchar('paytm_mkey', 255)->nullable();
            $table->integer('referral_amount')->nullable();
            $table->integer('force_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_home');
    }
};
