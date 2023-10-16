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
        Schema::create('tbl_paytm_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_reference', 50)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('amount', 11)->nullable();
            $table->date('order_date')->nullable();
            $table->datetime('datetime')->nullable();
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
        Schema::dropIfExists('tbl_paytm_payment_logs');
    }
};
