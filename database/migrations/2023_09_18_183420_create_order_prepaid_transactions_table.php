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
        Schema::create('tbl_order_prepaid_transaction', function (Blueprint $table) {
            $table->id();
            $table->date('order_date')->nullable();
            $table->integer('order_reference')->nullable();
            $table->string('order_id', 255)->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->float('basic_amount', 10, 2)->nullable();
            $table->float('edit_basic_amount', 10, 2)->nullable();
            $table->float('return_basic_amount', 10, 2)->nullable();
            $table->float('gross_amount', 10, 2)->nullable();
            $table->float('aggregator_commission_amount', 10, 2)->nullable();
            $table->float('edit_aggregator_commission_amount', 10, 2)->nullable();
            $table->float('return_aggregator_commission_amount', 10, 2)->nullable();
            $table->float('pgComRate', 10, 2)->nullable();
            $table->float('pgComGstRate', 10, 2)->nullable();
            $table->float('pgComAmount', 10, 2)->nullable();
            $table->float('PgComGstAmount', 10, 2)->nullable();
            $table->float('payable_merchant_amount', 10, 2)->nullable();
            $table->float('total_payout_from_nodal_account', 10, 2)->nullable();
            $table->float('tcs', 10, 2)->nullable();
            $table->float('edit_tcs', 10, 2)->nullable();
            $table->float('return_tcs', 10, 2)->nullable();
            $table->float('tds', 10, 2)->nullable();
            $table->float('edit_tds', 10, 2)->nullable();
            $table->float('return_tds', 10, 2)->nullable();
            $table->float('paytmTotalSettledAmount', 10, 2)->nullable();
            $table->float('paytmTotalCommAmount', 10, 2)->nullable();
            $table->float('paytmTotalCommGstAmount', 10, 2)->nullable();
            $table->datetime('paytmSettledDate')->nullable();
            $table->string('utrNo', 60)->nullable();
            $table->integer('status')->default(0);

            $table->foreign("shop_id")->references("id")->on("tbl_shop");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_order_prepaid_transaction');
    }
};
