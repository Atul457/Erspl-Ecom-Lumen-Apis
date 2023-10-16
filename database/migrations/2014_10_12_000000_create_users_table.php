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
        Schema::create('tbl_registration', function (Blueprint $table) {
            $table->id();
            $table->text('token_id')->nullable();
            $table->string('login_token', 20)->nullable();
            $table->string('reg_type', 255)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('middle_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->text('image')->nullable();
            $table->string('mobile', 255)->nullable();
            $table->string('alt_mobile', 15)->nullable();
            $table->string('email', 255)->nullable();
            $table->integer('email_status')->default(0)->comment('0 = verification pending, 1 = verified email');
            $table->string('gender', 255)->nullable();
            $table->date('dob')->nullable();
            $table->string('password', 255)->nullable();
            $table->string('otp', 255)->nullable();
            $table->dateTime('otp_datetime')->nullable();
            $table->integer('attempt')->default(1);
            $table->string('referral_code', 20)->nullable();
            $table->string('referral_by', 20)->nullable();
            $table->integer('referral_status')->default(0);
            $table->dateTime('suspended_datetime')->nullable();
            $table->string('wallet_balance', 50)->nullable();
            $table->text('tInfo_temp')->nullable();
            $table->integer('guest_status')->default(0);
            $table->string('device_id', 100)->nullable();
            $table->integer('status')->default(0)->comment('0 = verification pending, 1 = verified user');
            $table->date('date')->nullable();
            $table->rememberToken();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_registration');
    }
};
