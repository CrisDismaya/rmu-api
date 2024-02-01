<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefurbishDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refurbish_details', function (Blueprint $table) {
            $table->id();
            $table->BigInteger('refurbish_id');
            $table->BigInteger('spare_parts');
            $table->float('price');
            $table->float('actual_price')->default(0);
            $table->enum('status',['na','done'])->nullable();
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
        Schema::dropIfExists('refurbish_details');
    }
}
