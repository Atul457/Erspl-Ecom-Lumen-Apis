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
        Schema::create('scategory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("parent_id");
            $table->string("subcategory_code")->nullable();
            $table->string("name");
            $table->longText("slug_url")->nullable();
            $table->string("image");
            $table->integer("sub_order");
            $table->longText("meta_title")->nullable();
            $table->longText("meta_description")->nullable();
            $table->longText("meta_keywords")->nullable();
            $table->integer("status")->nullable()->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->foreign('parent_id')->references('id')->on('acategory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scategory');
    }
};
