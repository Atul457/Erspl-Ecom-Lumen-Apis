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
        Schema::create('wallet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('order_reference')->nullable();
            $table->string('order_id')->nullable();
            $table->string('invoice_id')->nullable();
            $table->float('amount')->nullable();
            $table->text('remark')->nullable();
            $table->string('payment_status')->nullable()->comment("1=credit 2=debit");
            $table->string('referral_code')->nullable();
            $table->string('referral_by')->nullable();
            $table->string('txn_id')->nullable();
            $table->dateTime('txn_date')->nullable();
            $table->string('payment_mode')->nullable();
            $table->dateTime('date')->nullable();
            $table->integer('status')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('customer_id')->references('id')->on('tbl_registration');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet');
    }
};
