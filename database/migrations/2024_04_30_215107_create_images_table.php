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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->integer('size');
            $table->string('mime_type', 100);
            $table->string('file_name');
            $table->string('path');
            $table->smallInteger('height');
            $table->smallInteger('width');
            $table->string('attachable_type', 100)->nullable();
            $table->bigInteger('attachable_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
