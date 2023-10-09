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
            $table->string('otp');
            $table->string('mobile');
            $table->string('password');
            $table->string('reg_type');
            $table->date('dob')->nullable(); 
            $table->string('email')->unique();
            $table->string('image')->nullable();
            $table->string('token_id')->nullable();
            $table->integer('attempt')->default(0);
            $table->date('otp_datetime')->nullable();
            $table->string('last_name')->default("");
            $table->string('alt_mobile')->nullable();
            $table->string('first_name')->default("");
            $table->string('referral_by')->nullable();
            $table->string('middle_name')->default("");
            $table->integer('guest_status')->default(0);
            $table->string('referral_code')->nullable();
            $table->float('wallet_balance')->nullable();
            $table->longText('tInfo_temp')->nullable();
            $table->date('suspended_datetime')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('status')->default(1)->comment('0 = Inactive, 1 = Active');
            $table->tinyInteger('gender')->nullable()->comment('0 = Male, 1 = Female, 2 = Other');
            $table->integer('email_status')->default(0)->comment('0 = Verification pending, 1 = Email verified');
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
