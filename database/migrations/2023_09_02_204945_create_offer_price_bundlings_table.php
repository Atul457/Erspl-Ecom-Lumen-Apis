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
        Schema::create('tbl_offer_price_bundling', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->integer('offer_by')->nullable()->comment('1=RSPL 2=SELLER');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->integer('minimum_offer_value')->nullable();
            $table->integer('offer_unique_id')->nullable();
            $table->integer('offer_amount')->nullable();
            $table->text('description');
            $table->string('time_used', 10)->nullable();
            $table->datetime('live_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->integer('status')->default(0);
            $table->datetime('created_date')->nullable();

            $table->foreign("shop_id")->references("id")->on("tbl_shop");
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_offer_price_bundling');
        Schema::enableForeignKeyConstraints();
    }
};
