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
        Schema::create('tbl_wallet', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('order_reference', 50)->nullable();
            $table->string('order_id', 50)->nullable();
            $table->string('invoice_id', 50)->nullable();
            $table->float('amount', 10, 2)->nullable();
            $table->text('remark');
            $table->string('payment_status', 11)->nullable()->comment('1=credit 2=debit');
            $table->string('referral_code', 20)->nullable();
            $table->string('referral_by', 20)->nullable();
            $table->string('txn_id', 255)->nullable();
            $table->datetime('txn_date')->nullable();
            $table->string('payment_mode', 255)->nullable();
            $table->datetime('date')->nullable();
            $table->integer('status')->default(0);

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
        Schema::dropIfExists('tbl_wallet');
    }
};
