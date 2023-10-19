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
        Schema::create('tbl_scategory', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->unsignedBigInteger('parent_id');
            $table->string('subcategory_code', 255)->nullable();
            $table->string('name', 255);
            $table->text('slug_url');
            $table->string('image', 255);
            $table->integer('sub_order');
            $table->text('meta_title');
            $table->text('meta_description');
            $table->text('meta_keywords');
            $table->integer('status')->default(1);

            $table->foreign('parent_id')->references('id')->on('tbl_acategory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_scategory');
    }
};
