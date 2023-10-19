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
        Schema::create('tbl_notification', function (Blueprint $table) {
            $table->id(); // This will automatically create an 'id' column with auto-incrementing.
            $table->string('title', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('image', 255)->nullable();
            $table->integer('app_id')->nullable()->comment('1=eRSPL 2=CEO 3=STAR');
            $table->datetime('date')->nullable();
            $table->integer('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_notification');
    }
};
