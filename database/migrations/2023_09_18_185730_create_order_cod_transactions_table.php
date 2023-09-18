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
        Schema::create('tbl_order_cod_transaction', function (Blueprint $table) {
            $table->id();
            $table->date("order_date")->nullable();
            $table->integer("order_reference")->nullable();
            $table->integer("order_id")->nullable();
            $table->unsignedBigInteger("shop_id")->nullable();
            $table->float("basic_amount")->nullable();
            $table->float("edit_basic_amount")->nullable();
            $table->float("return_basic_amount")->nullable();
            $table->float("gross_amount")->nullable();
            $table->float("aggregator_commission_amount")->nullable();
            $table->float("edit_aggregator_commission_amount")->nullable();
            $table->float("return_aggregator_commission_amount")->nullable();
            $table->float("pgComRate")->nullable();
            $table->float("pgComGstRate")->nullable();
            $table->float("pgComAmount")->nullable();
            $table->float("PgComGstAmount")->nullable();
            $table->float("payable_merchant_amount")->nullable();
            $table->float("total_payout_from_nodal_account")->nullable();
            $table->float("tcs")->nullable();
            $table->float("edit_tcs")->nullable();
            $table->float("return_tcs")->nullable();
            $table->float("tds")->nullable();
            $table->float("edit_tds")->nullable();
            $table->float("return_tds")->nullable();
            $table->float("paytmTotalSettledAmount")->nullable();
            $table->float("paytmTotalCommAmount")->nullable();
            $table->float("paytmTotalCommGstAmount")->nullable();
            $table->dateTime("paytmSettledDate")->nullable();
            $table->string("utrNo")->nullable();
            $table->integer("status")->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign("shop_id")->references("id")->on("shop");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_order_cod_transaction');
    }
};
