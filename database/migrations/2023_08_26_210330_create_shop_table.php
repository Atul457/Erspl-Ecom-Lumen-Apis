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
        Schema::create('shop', function (Blueprint $table) {
            $table->id();
            $table->longText("token_id")->nullable();
            $table->string("shop_code")->nullable();
            $table->string("name")->nullable();
            $table->longText("slug_url")->nullable();
            $table->integer("type_id")->nullable();
            $table->string("owner_name")->nullable();
            $table->string("mobile")->nullable();
            $table->string("email")->nullable();
            $table->string("voter_id")->nullable();
            $table->string("pancard_no")->nullable();
            $table->string("aadhar_no")->nullable();
            $table->integer("otp")->nullable();
            $table->unsignedBigInteger("state_id")->nullable();
            $table->unsignedBigInteger("city_id")->nullable();
            $table->string("pincode")->nullable();
            $table->date("doj")->nullable();
            $table->string("password")->nullable();
            $table->string("website_link")->nullable();
            $table->longText("address")->nullable();
            $table->string("latitude")->nullable();
            $table->string("longitude")->nullable();
            $table->string("delivery_time")->nullable();
            $table->integer("discount_status")->nullable()->default(0);
            $table->string("discount_discription")->nullable()->default(0);
            $table->integer("max_qty")->default(0)->comment("per product qty");
            $table->time("time_from")->nullable();
            $table->time("time_to")->nullable();
            $table->string("account_name")->nullable();
            $table->string("account_no")->nullable();
            $table->string("bank_name")->nullable();
            $table->string("branch_name")->nullable();
            $table->string("ifsc_code")->nullable();
            $table->string("gst_no")->nullable();
            $table->longText("cancel_cheque_photo")->nullable();
            $table->longText("gst_photo")->nullable();
            $table->longText("pancard_photo")->nullable();
            $table->longText("voter_photo")->nullable();
            $table->longText("aadhar_front_photo")->nullable();
            $table->longText("aadhar_back_photo")->nullable();
            $table->longText("shopkeeper_photo_1")->nullable();
            $table->longText("shopkeeper_photo_2")->nullable();
            $table->longText("food_license")->nullable();
            $table->string("msme_photo")->nullable();
            $table->string("mid")->nullable();
            $table->string("mkey")->nullable();
            $table->string("image")->nullable();
            $table->string("self_delivery")->nullable();
            $table->string("cod")->nullable();
            $table->string("pickup_facility")->nullable();
            $table->longText("tInfo_temp")->nullable();
            $table->integer("status")->default(0)->comment("3 = DELETED, 1 = ACTIVE , 2 = INACTIVE, 0 = PENDING, 4 = UNDER REVIEW, 5 = REJECTED");
            $table->integer("store_status")->default(1)->comment("1=open 2=close");
            $table->string("action_by")->nullable();
            $table->string("action_remark")->nullable();
            $table->date("action_date")->nullable();
            $table->date("date")->nullable();
            $table->string("verify_by")->nullable();
            $table->date("verify_date")->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign("city_id")->references("id")->on("tbl_city");
            $table->foreign("state_id")->references("id")->on("state");

        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('shop');
        Schema::enableForeignKeyConstraints();
    }
};
