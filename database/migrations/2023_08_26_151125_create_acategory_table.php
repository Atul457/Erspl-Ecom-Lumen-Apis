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
        Schema::create('tbl_acategory', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->unsignedBigInteger('industries_id')->nullable();
            $table->string('category_code', 255)->nullable();
            $table->string('name', 255);
            $table->text('offer_title');
            $table->text('icon');
            $table->text('catlog');
            $table->string('image', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->integer('category_order')->nullable();
            $table->integer('status')->default(1);
            $table->text('meta_title');
            $table->text('meta_description');
            $table->text('meta_keywords');
            $table->text('slug_url');
            $table->text('slider');

            $table->foreign('industries_id')->references('id')->on('tbl_industries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_acategory');
    }
};
