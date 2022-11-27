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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->unique(['name'], 'menu_item_name');
            $table->string('type', 10);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('link_id')->nullable();
            $table->foreign('link_id')->references('id')->on('links');
            $table->integer('ord')->nullable();
            $table->timestamps();
        });

        Schema::table('menu_items', function (Blueprint $table)
        {
            $table->foreign('parent_id')->references('id')->on('menu_items');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_items');
    }
};
