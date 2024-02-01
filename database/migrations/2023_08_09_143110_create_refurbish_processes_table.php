<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefurbishProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refurbish_processes', function (Blueprint $table) {
            $table->id();
            $table->BigInteger('refurbish_req_id');
            $table->json('files_names')->nullable();
            $table->BigInteger('maker');
            $table->BigInteger('approver')->nullable();
            $table->enum('status',['0','1','2'])->default('0');
            $table->string('remarks')->nullable();
            $table->string('re_class')->nullable();
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
        Schema::dropIfExists('refurbish_processes');
    }
}
