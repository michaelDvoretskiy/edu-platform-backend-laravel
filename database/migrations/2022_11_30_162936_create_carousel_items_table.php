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
        Schema::create('carousel_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('carousel_id')->nullable();
            $table->foreign('carousel_id')->references('id')->on('carousels');
            $table->string('name', 50);
            $table->unique(['carousel_id', 'name'], 'carousel_items_unique');
            $table->json('title');
            $table->json('content_text');
            $table->string('img_path', 100);
            $table->boolean('btn_flag');
            $table->unsignedBigInteger('link_id')->nullable();
            $table->foreign('link_id')->references('id')->on('links');
            $table->integer('ord')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carousel_items');
    }
};
