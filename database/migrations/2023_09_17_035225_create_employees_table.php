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
            $table->string("token_id")->nullable();
            $table->integer('otp')->nullable();
            $table->string('device_id')->nullable();
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('alt_mobile')->nullable();
            $table->string('password')->nullable();
            $table->string('email')->nullable();
            $table->string('designation_id')->nullable();
            $table->text('tInfo_temp')->nullable();
            $table->integer('firm_type')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->text('city_info')->nullable();
            $table->string('area')->nullable();
            $table->string('pincode')->nullable();
            $table->text('address')->nullable();
            $table->text('address_2')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('emp_code')->nullable();
            $table->date('dob')->nullable();
            $table->date('doj')->nullable();
            $table->string('salary')->nullable();
            $table->text('photo')->nullable();
            $table->string('gst')->nullable();
            $table->string('aadhar_no')->nullable();
            $table->text('shop_front_photo')->nullable();
            $table->text('shop_inside_photo')->nullable();
            $table->text('shop_owner_photo')->nullable();
            $table->integer('dp_type')->default(0);
            $table->integer('status')->nullable()->default(1)->comment("0 = DELETED, 1 = ACTIVE , 2 = INACTIVE, 3 = PENDING, 4 = UNDER REVIEW, 5 = REJECTED");
            $table->integer('online_status')->default(0);
            $table->integer('assign_status')->default(0);
            $table->string('credit_limit')->nullable();
            $table->string('credit_days')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('assign_id')->nullable();
            $table->string('added_by')->nullable();
            $table->dateTime('last_login')->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->foreign("city_id")->references("id")->on("city");
            $table->foreign("state_id")->references("id")->on("state");
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
