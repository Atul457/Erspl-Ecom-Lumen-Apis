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
        Schema::create('slider', function (Blueprint $table) {
            $table->id();
            $table->string("title")->nullable();
            $table->string("sub_title")->nullable();
            $table->string("slider")->nullable();
            $table->unsignedBigInteger("shop_id")->nullable();
            $table->string("link")->nullable();
            $table->integer("sort_order")->nullable();
            $table->integer("status")->nullable()->default(1);
            $table->unsignedBigInteger("created_by")->nullable();
            $table->dateTime("datetime")->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->foreign("shop_id")->references("id")->on("shop");
            $table->foreign("created_by")->references("id")->on("tbl_registration");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('slider');
        Schema::enableForeignKeyConstraints();
    }
};
