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
        Schema::create('tbl_cancel_reason', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->integer("type")->nullable()->comment("1=user 2=shop 3=deliveryboy");
            $table->integer("status")->nullable()->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_cancel_reason');
    }
};
