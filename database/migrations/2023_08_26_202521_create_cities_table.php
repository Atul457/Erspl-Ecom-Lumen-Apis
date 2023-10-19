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
        Schema::create('tbl_city', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("parent_id");
            $table->string("name");
            $table->integer("code")->nullable();
            $table->foreign('parent_id')->references("id")->on("tbl_state");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_city');
    }
};