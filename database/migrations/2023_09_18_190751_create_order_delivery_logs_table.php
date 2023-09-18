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
        Schema::create('tbl_order_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->integer("order_id")->nullable();
            $table->string("remark")->nullable();
            $table->unsignedBigInteger("cust_id")->nullable();
            $table->unsignedBigInteger("shop_id")->nullable();
            $table->unsignedBigInteger("emp_id")->nullable();
            $table->dateTime("datetime")->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->foreign("shop_id")->references("id")->on("shop");
            $table->foreign("cust_id")->references("id")->on("tbl_registration");
            $table->foreign("emp_id")->references("id")->on("tbl_employee");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_order_delivery_logs');
    }
};
