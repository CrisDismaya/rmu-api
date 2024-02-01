<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoldUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sold_units', function (Blueprint $table) {
            $table->id();
            $table->BigInteger('repo_id');
            $table->BigInteger('branch');
            $table->BigInteger('new_customer');
            $table->string('invoice_reference_no');
            $table->string('ExternalReference')->nullable();
            $table->string('AgentID')->nullable();
            $table->enum('sale_type',['I','C']);
            $table->float('srp');
            $table->float('dp')->nullable();
            $table->float('amount_paid')->nullable();
            $table->float('monthly_amo')->nullable();
            $table->float('rebate')->nullable();
            $table->integer('terms')->nullable();
            $table->float('rate')->nullable();
            $table->float('interest_rate')->nullable();
            $table->float('amount_finance')->nullable();
            $table->date('sold_date');
            $table->BigInteger('maker');
            $table->BigInteger('approver');
            $table->string('file_name')->nullable();
            $table->string('path')->nullable();
            $table->enum('status',[0,1,2])->default('0');
            $table->string('remarks');
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
        Schema::dropIfExists('sold_units');
    }
}
