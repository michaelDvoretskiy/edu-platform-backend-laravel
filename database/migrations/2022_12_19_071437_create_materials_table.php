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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->json('title');
            $table->string('type');
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->foreign('lesson_id')->references('id')->on('lessons');
            $table->unsignedBigInteger('link_id')->nullable();
            $table->foreign('link_id')->references('id')->on('links');
            $table->unsignedBigInteger('pdf_storage_id')->nullable();
            $table->foreign('pdf_storage_id')->references('id')->on('pdf_storages');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('materials');
    }
};
