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
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 50)->unique();
            $table->string('img_path', 100);
            $table->json('title');
            $table->json('profession');
            $table->json('title_full');
            $table->json('profession_full');
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->foreign('menu_item_id')->references('id')->on('menu_items');
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
        Schema::dropIfExists('team_members');
    }
};
