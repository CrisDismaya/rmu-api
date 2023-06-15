<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerProfileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_profile', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('acumatica_id');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->string('contact');
            $table->string('address');
            $table->string('provinces')->nullable();
            $table->string('cities')->nullable();
            $table->string('barangays')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('nationality')->nullable();
            $table->string('source_of_income')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('date_birth')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('primary_id')->nullable();
            $table->string('primary_id_no')->nullable();
            $table->string('alternative_id')->nullable();
            $table->string('alternative_id_no')->nullable();
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
        Schema::dropIfExists('customer_profile');
    }
}
