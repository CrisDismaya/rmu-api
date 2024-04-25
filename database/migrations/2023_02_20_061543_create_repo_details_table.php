<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRepoDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repo_details', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('branch_id');
            $table->integer('location');
            $table->string('customer_acumatica_id');
            $table->integer('brand_id');
            $table->integer('model_id');
            $table->string('plate_number');
            $table->string('model_engine');
            $table->string('model_chassis');
            $table->integer('color_id');
            $table->string('mv_file_number')->nullable();
            $table->string('classification');
            $table->integer('year_model');
            $table->double('original_srp');
            $table->date('date_sold');
            $table->integer('transfer_branch_id')->nullable();
            $table->date('date_surrender');
            $table->string('msuisva_form_no')->nullable();
            $table->string('loan_number');
            $table->string('odo_meter');
            $table->string('unit_description');
            $table->string('unit_documents');
            $table->date('last_payment')->nullable();
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
        Schema::dropIfExists('repo_details');
    }
}
