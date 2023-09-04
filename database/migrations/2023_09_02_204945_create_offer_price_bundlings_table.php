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
        Schema::disableForeignKeyConstraints();
        Schema::create('offer_price_bundling', function (Blueprint $table) {
            $table->id();
            $table->string("offer_by")->nullable()->comment("1=RSPL 2=SELLER");
            $table->unsignedBigInteger("shop_id")->nullable();
            $table->integer("minimum_offer_value")->nullable();
            $table->integer("offer_unique_id")->nullable();
            $table->integer("offer_amount")->nullable();
            $table->text("description")->nullable()->default("");
            $table->string("time_used")->nullable();
            $table->dateTime("live_date")->nullable();
            $table->dateTime("end_date")->nullable();
            $table->integer("status")->nullable();
            $table->dateTime("created_date")->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->foreign("shop_id")->references("id")->on("shop");
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('offer_price_bundling');
        Schema::enableForeignKeyConstraints();
    }
};
