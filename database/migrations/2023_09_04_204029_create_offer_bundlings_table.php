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
        Schema::create('tbl_offer_bundling', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->integer('primary_unique_id')->nullable();
            $table->integer('offer_unique_id')->nullable();
            $table->integer('offer_amount')->nullable();
            $table->text('description');
            $table->integer('status')->default(0);
            $table->datetime('created_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_offer_bundling');
    }
};
