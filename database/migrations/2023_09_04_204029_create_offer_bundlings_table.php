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
        Schema::create('offer_bundling', function (Blueprint $table) {
            $table->id();
            $table->integer("primary_unique_id")->nullable();
            $table->integer("offer_unique_id")->nullable();
            $table->integer("offer_amount")->nullable();
            $table->string("description")->nullable()->default("");
            $table->integer("status")->nullable()->default(0);
            $table->dateTime("created_date")->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_bundling');
    }
};
