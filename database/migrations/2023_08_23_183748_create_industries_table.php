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
        Schema::create('tbl_industries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default("");
            $table->string('offer_title')->nullable();
            $table->integer('category_order')->nullable();
            $table->longText('icon')->nullable();
            $table->longText('catlog')->nullable();
            $table->string('image')->nullable();
            $table->string('photo')->nullable();
            $table->string('slug_url')->default("");
            $table->longText('meta_title')->nullable();
            $table->longText('meta_keywords')->nullable();
            $table->longText('meta_description')->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_industries');
    }
};
