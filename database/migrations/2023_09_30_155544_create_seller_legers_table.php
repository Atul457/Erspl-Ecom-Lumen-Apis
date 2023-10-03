<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tbl_seller_ledger', function (Blueprint $table) {
            $table->id();
            $table->date('order_date')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('shop_name')->nullable();
            $table->unsignedBigInteger('shop_city_id')->nullable();
            $table->string('order_reference', 50)->nullable();
            $table->string('order_id', 50)->nullable();
            $table->text('transaction_detail')->nullable();
            $table->string('payment_mode', 50)->nullable();
            $table->string('particular', 255)->nullable();
            $table->float('debit', 10, 2)->nullable();
            $table->float('credit', 10, 2)->nullable();
            $table->float('balance', 10, 2)->nullable();
            $table->integer('case')->nullable()->comment('1=order place amount 0= reversal/updated 2=pgComm');
            $table->datetime('datetime')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign("shop_id")->references("id")->on("shop");
            $table->foreign('shop_city_id')->references('id')->on('city');

        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_seller_ledger');
    }
};
