<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecieveUnitDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recieve_unit_details', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('branch');
            $table->integer('repo_id');
            $table->double('unit_price');
            $table->enum('status', [0, 1, 2])->default(0);
            $table->enum('is_sold', ['Y','N'])->default('N');
            $table->enum('sold_type',['I','C'])->nullable();
            $table->string('loan_amount')->nullable();
            $table->string('total_payments')->nullable();
            $table->string('principal_balance')->nullable();
            $table->string('is_certified_no_parts')->nullable();
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
        Schema::dropIfExists('recieve_unit_details');
    }
}
