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
            $table->string('plate_number');
            $table->string('model_engine');
            $table->string('model_chassis');
            $table->integer('color_id');
            $table->string('mv_file_number');
            $table->string('type');
            $table->string('classification');
            $table->string('series');
            $table->string('body');
            $table->integer('year_model');
            $table->string('gross_vehicle_weight');
            $table->double('original_srp');
            $table->date('date_sold');
            $table->string('insurer');
            $table->string('cert_cover_no');
            $table->date('expiry_date');
            $table->string('encumbered_to')->nullable();
            $table->string('leased_to')->nullable();
            $table->string('latest_or_number');
            $table->date('date_last_registration');
            $table->double('amount_paid');
            $table->integer('transfer_branch_id')->nullable();
            $table->date('date_surrender');
            $table->string('msuisva_form_no');
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
