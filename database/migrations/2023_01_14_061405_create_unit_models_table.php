<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_models', function (Blueprint $table) {
            $table->bigInteger('id',true);
            $table->bigInteger('brand_id');
            $table->string('inventory_code')->nullable();
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->string('model_name');
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
        Schema::dropIfExists('unit_models');
        Schema::table('unit_models', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
        });
    }
}
