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
            $table->id();
            $table->string('name')->default("");
            $table->unsignedBigInteger('industries_id')->nullable();
            $table->longText('offer_title')->nullable();
            $table->longText('icon')->nullable();
            $table->longText('catlog')->nullable();
            $table->string('image')->nullable();
            $table->string('photo')->nullable();
            $table->string('category_code')->nullable();
            $table->integer('category_order')->nullable(); 
            $table->integer('status')->nullable()->default(1);
            $table->longText('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->longText('meta_keywords')->nullable();
            $table->longText('slug_url')->nullable();
            $table->longText('slider')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            
            $table->foreign('industries_id')->references('id')->on('industries');
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
