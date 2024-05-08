<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppraisalHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appraisal_histories', function (Blueprint $table) {
            $table->id();
            $table->BigInteger('appraisal_req_id');
            $table->integer('branch')->nullable();
            $table->float('old_price')->nullable();
            $table->float('appraised_price')->nullable();
            $table->date('date_approved')->nullable();
            $table->string('remarks')->nullable();
            $table->BigInteger('approver')->nullable();
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
        Schema::dropIfExists('appraisal_histories');
    }
}
