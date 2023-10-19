<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tbl_wrong_reg', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->dateTime('datetime')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('tbl_wrong_reg');
    }
};
