<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProvincesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_provinces', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('code');
            $table->string('name');
            $table->string('regionCode');
            $table->string('islandGroupCode');
            $table->string('psgc10DigitCode');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_provinces');
    }
}
