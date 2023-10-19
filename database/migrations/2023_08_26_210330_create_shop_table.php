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
        Schema::create('tbl_shop', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->text('token_id');
            $table->string('shop_code', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->text('slug_url');
            $table->integer('type_id')->nullable();
            $table->string('owner_name', 255)->nullable();
            $table->string('mobile', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('voter_id', 255)->nullable();
            $table->string('pancard_no', 255)->nullable();
            $table->string('aadhar_no', 255)->nullable();
            $table->integer('otp')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('pincode', 255)->nullable();
            $table->date('doj')->nullable();
            $table->string('password', 255)->nullable();
            $table->string('website_link', 255)->nullable();
            $table->text('address');
            $table->string('latitude', 30)->nullable();
            $table->string('longitude', 30)->nullable();
            $table->string('delivery_time', 20)->nullable();
            $table->integer('discount_status')->default(0);
            $table->string('discount_discription', 255)->nullable();
            $table->integer('max_qty')->default(5)->comment('per product qty');
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->string('account_name', 100)->nullable();
            $table->string('account_no', 20)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('branch_name', 255)->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('gst_no', 20)->nullable();
            $table->text('cancel_cheque_photo');
            $table->text('gst_photo');
            $table->text('pancard_photo');
            $table->text('voter_photo');
            $table->text('aadhar_front_photo');
            $table->text('aadhar_back_photo');
            $table->text('shopkeeper_photo_1');
            $table->text('shopkeeper_photo_2');
            $table->text('food_license');
            $table->string('msme_photo', 255)->nullable();
            $table->string('mid', 255)->nullable();
            $table->string('mkey', 255)->nullable();
            $table->string('image', 225)->nullable();
            $table->string('self_delivery', 5)->nullable();
            $table->string('cod', 15)->nullable();
            $table->string('pickup_facility', 50)->nullable();
            $table->text('tInfo_temp');
            $table->integer('status')->default(0)->comment('3 = DELETED, 1 = ACTIVE , 2 = INACTIVE, 0 = PENDING, 4 = UNDER REVIEW, 5 = REJECTED');
            $table->integer('store_status')->default(1)->comment('1=open 2=close');
            $table->string('action_by', 255)->nullable();
            $table->string('action_remark', 255)->nullable();
            $table->date('action_date')->nullable();
            $table->datetime('date')->nullable();
            $table->string('verify_by', 255)->nullable();
            $table->date('verify_date')->nullable();

            $table->foreign("city_id")->references("id")->on("tbl_city");
            $table->foreign("state_id")->references("id")->on("tbl_state");

        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_shop');
        Schema::enableForeignKeyConstraints();
    }
};
