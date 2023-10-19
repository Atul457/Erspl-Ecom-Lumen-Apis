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
        Schema::create('tbl_employee', function (Blueprint $table) {
            $table->id();
            $table->text('token_id')->nullable();
            $table->integer('otp')->nullable();
            $table->string('device_id', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('company_name', 255)->nullable();
            $table->string('mobile', 255)->nullable();
            $table->string('alt_mobile', 20)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('designation_id', 5)->nullable();
            $table->text('tInfo_temp')->nullable();
            $table->integer('firm_type')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->text('city_info')->nullable();
            $table->string('area', 255)->nullable();
            $table->string('pincode', 255)->nullable();
            $table->text('address')->nullable();
            $table->text('address_2')->nullable();
            $table->string('vendor_id', 255)->nullable();
            $table->string('emp_code', 255)->nullable();
            $table->date('dob')->nullable();
            $table->date('doj')->nullable();
            $table->string('salary', 11)->nullable();
            $table->text('photo')->nullable();
            $table->string('gst', 255)->nullable();
            $table->string('aadhar_no', 20)->nullable();
            $table->text('shop_front_photo')->nullable();
            $table->text('shop_inside_photo')->nullable();
            $table->text('shop_owner_photo')->nullable();
            $table->integer('dp_type')->default(0);
            $table->integer('status')->default(1)->comment('0 = DELETED, 1 = ACTIVE , 2 = INACTIVE, 3 = PENDING, 4 = UNDER REVIEW, 5 = REJECTED');
            $table->integer('online_status')->default(0);
            $table->integer('assign_status')->default(0);
            $table->string('credit_limit', 10)->nullable();
            $table->string('credit_days', 5)->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->string('assign_id', 5)->nullable();
            $table->string('added_by', 5)->nullable();
            $table->datetime('last_login')->nullable();
            $table->datetime('date');

            $table->foreign("city_id")->references("id")->on("tbl_city");
            $table->foreign("state_id")->references("id")->on("tbl_state");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_employee');
    }
};
