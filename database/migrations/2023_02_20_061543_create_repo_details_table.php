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
            $table->string('customer_acumatica_id');
            $table->integer('brand_id');
            $table->integer('model_id');
            $table->string('model_engine');
            $table->string('model_chassis');
            $table->integer('color_id');
            $table->string('plate_number')->nullable();
            $table->string('mv_file_number')->nullable();
            $table->integer('year_model');
            $table->string('orcr_status');
            $table->string('unit_documents');
            $table->date('date_sold');
            $table->date('date_surrender');
            $table->double('original_srp');
            $table->date('last_payment')->nullable();
            $table->string('loan_number');
            $table->string('odo_meter');
            $table->integer('location');
            $table->integer('times_repossessed');
            $table->string('repossessed_exowner')->nullable();
            $table->string('apprehension');
            $table->string('apprehension_description');
            $table->string('apprehension_summary');
            $table->integer('transfer_branch_id')->nullable();
            $table->string('classification')->nullable();
            $table->string('unit_description')->nullable();
            $table->string('msuisva_form_no')->nullable();
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
