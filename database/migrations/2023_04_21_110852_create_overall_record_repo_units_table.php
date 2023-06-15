<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOverallRecordRepoUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repo_unit_history', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('branch_id');
            $table->integer('repo_id');
            $table->integer('customer_id');
            $table->integer('received_id')->nullable();
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
        Schema::dropIfExists('repo_unit_history');
    }
}
