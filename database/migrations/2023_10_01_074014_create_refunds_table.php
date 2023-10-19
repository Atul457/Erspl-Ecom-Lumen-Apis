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
        Schema::create('tbl_refund', function (Blueprint $table) {
            $table->id();
            $table->date('order_date')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('order_reference', 20)->nullable();
            $table->string('order_id', 20)->nullable();
            $table->string('incomimg_txn_id', 50)->nullable();
            $table->string('source', 50)->nullable();
            $table->string('orignal_order_total', 20)->nullable();
            $table->string('edit_order_total', 20)->nullable();
            $table->date('refund_date')->nullable();
            $table->string('ref_id', 50)->nullable();
            $table->text('refund_txn_id')->nullable();
            $table->string('refund_msg', 255)->nullable();
            $table->string('refund_amount', 50)->nullable();
            $table->float('tds', 10, 2)->nullable();
            $table->float('tcs', 10, 2)->nullable();
            $table->string('outgoing_txn_id', 50)->nullable();
            $table->text('refund_remark')->nullable();
            $table->integer('added_by')->nullable();
            $table->dateTime('datetime')->nullable();
            $table->string('reason', 255)->nullable();
            $table->integer('status')->default(0);

            $table->foreign('customer_id')->references('id')->on('tbl_registration');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_refund');
    }
};
