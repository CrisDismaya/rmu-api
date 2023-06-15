<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitAgingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_agings', function (Blueprint $table) {
            $table->id();
            $table->string('days');
            $table->string('Depreceiation_Cost');
            $table->string('Estimated_Cost_of_MD_Parts');
            $table->string('Max_Depreciation_from_Original_SP');
            $table->string('Immediate_Sales_Value');
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
        Schema::dropIfExists('unit_agings');
    }
}
