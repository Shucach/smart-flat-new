<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAirsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('airs', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('type');
            $table->float('co', 8, 5)->default(0.0);
            $table->float('lpg', 8, 5)->default(0.0);
            $table->float('smoke', 8, 5)->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airs');
    }
}
