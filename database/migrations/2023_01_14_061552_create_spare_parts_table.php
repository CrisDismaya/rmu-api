<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSparePartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->bigInteger('id',true);
            $table->string('inventory_code');
            $table->bigInteger('model_id');
            $table->foreign('model_id')->references('id')->on('unit_models');
            $table->string('name');
            $table->double('price');
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
        Schema::dropIfExists('spare_parts');
        Schema::table('spare_parts', function (Blueprint $table) {
            $table->dropForeign(['model_id']);
        });
    }
}
