<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaDownloadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_downloads', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('process_id')->default(0);
            $table->string('torrent_name', 1000)->default('');
            $table->string('folder_path', 1000)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_downloads');
    }
}
