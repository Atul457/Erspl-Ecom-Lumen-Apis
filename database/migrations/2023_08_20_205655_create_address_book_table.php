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
            $table->id();
            $table->string("name")->nullable()->collation('utf8_general_ci');
            $table->string('city')->nullable()->collation('utf8_general_ci');
            $table->string('flat')->nullable()->collation('utf8_general_ci');
            $table->string('state')->nullable()->collation('utf8_general_ci');
            $table->string('mobile')->nullable()->collation('utf8_general_ci');
            $table->string('pincode')->nullable()->collation('utf8_general_ci');
            $table->string('country')->nullable()->collation('utf8_general_ci');
            $table->string('latitude')->nullable()->collation('utf8_general_ci');
            $table->string('landmark')->nullable()->collation('utf8_general_ci');
            $table->string('longitude')->nullable()->collation('utf8_general_ci');
            $table->longText('address')->nullable()->collation('utf8_general_ci');
            $table->unsignedBigInteger('customer_id');
            $table->tinyInteger('default_status')->default(0);
            $table->integer('address_type')->nullable()->comment("1 = home, 2 = work, 3 = other");

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
        Schema::dropIfExists('tbl_addressbook');
    }
};
