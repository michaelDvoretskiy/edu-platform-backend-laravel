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
        Schema::create('test_zone_times', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('zone_id');
            $table->foreign('zone_id')->references('id')->on('test_zones');
            $table->unsignedInteger('minutes');
            $table->decimal('koef', 4, 2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_zone_times');
    }
};
