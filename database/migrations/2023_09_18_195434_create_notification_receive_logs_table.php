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
        Schema::create('tbl_notification_receive_logs', function (Blueprint $table) {
            $table->id();
            $table->string("order_id")->nullable();
            $table->string("sent_remark")->nullable();
            $table->dateTime("sent_time")->nullable();
            $table->string("receive_remark")->nullable();
            $table->dateTime("receive_time")->nullable();
            $table->dateTime("datetime")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_notification_receive_logs');
    }
};
