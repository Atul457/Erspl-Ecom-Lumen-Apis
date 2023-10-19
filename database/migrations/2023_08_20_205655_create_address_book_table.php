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
        Schema::create('tbl_addressbook', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->string('name', 225);
            $table->string('mobile', 15)->nullable();
            $table->string('pincode', 8)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('country', 255)->nullable();
            $table->string('address', 225)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('flat', 255)->nullable();
            $table->string('landmark', 255)->nullable();
            $table->integer('address_type')->nullable()->comment('1 = home, 2 = work, 3 = other');
            $table->integer('default_status')->default(0);
            $table->string('latitude', 25)->nullable();
            $table->string('longitude', 25)->nullable();

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
        Schema::dropIfExists('tbl_addressbook');
    }
};
