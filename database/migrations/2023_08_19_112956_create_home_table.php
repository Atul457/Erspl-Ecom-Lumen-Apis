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
        Schema::create('home', function (Blueprint $table) {
            $table->id();
            $table->float("gst");
            $table->string("logo")->collation('utf8_general_ci');
            $table->string("email")->collation('utf8_general_ci');
            $table->string("title")->collation('utf8_general_ci');
            $table->string("title2")->collation('utf8_general_ci');
            $table->string("title1")->collation('utf8_general_ci');
            $table->float("version");
            $table->string("contact")->collation('utf8_general_ci');
            $table->string("zap_key")->collation('utf8_general_ci');
            $table->integer("count2");
            $table->integer("count1");
            $table->string("fav_icon")->collation('utf8_general_ci');
            $table->string("gallery1")->collation('utf8_general_ci');
            $table->string("gallery2")->collation('utf8_general_ci');
            $table->longText("address")->collation('utf8_general_ci');
            $table->string("paytm_mid")->collation('utf8_general_ci');
            $table->string("paytm_mkey")->collation('utf8_general_ci');
            $table->integer("category2");
            $table->string("tInfo_temp")->collation('utf8_general_ci');
            $table->string("sms_vendor")->collation('utf8_general_ci');
            $table->integer("shop_range");
            $table->string("fortius_key")->collation('utf8_general_ci');
            $table->string("company_name")->collation('utf8_general_ci');
            $table->string("meta_keyword")->collation('utf8_general_ci');
            $table->integer("category1_id");
            $table->integer("category2_id");
            $table->float("packing_charge");
            $table->integer("shop_capping");
            $table->integer("force_status");
            $table->integer("wallet_status");
            $table->float("referral_amount");
            $table->integer("minimum_value");
            $table->float("delivery_charge");
            $table->integer("order_capping");
            $table->integer("prepaid_status");
            $table->integer("weight_capping");
            $table->string("meta_description")->collation('utf8_general_ci');
            $table->string("notification_key")->collation('utf8_general_ci');
            $table->longText("footer_description")->collation('utf8_general_ci');
            $table->integer("delivery_type")->comment("1=Free, 2=Paid");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('home');
    }
};
