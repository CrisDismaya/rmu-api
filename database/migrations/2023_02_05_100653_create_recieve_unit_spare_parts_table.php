<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecieveUnitSparePartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recieve_unit_spare_parts', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('recieve_id');
            $table->foreign('recieve_id')->references('id')->on('recieve_unit_details');
            $table->integer('parts_id');
            $table->string('parts_status');
            $table->double('price');
            $table->string('parts_remarks')->nullable();
			$table->enum('is_deleted', [0, 1])->default(0);
            $table->decimal('actual_price', 10, 2)->nullable();
            $table->string('refurb_decision')->nullable();
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
        Schema::dropIfExists('recieve_unit_spare_parts');
        Schema::table('recieve_unit_details', function (Blueprint $table) {
            $table->dropForeign(['recieve_id']);
        });
    }
}
