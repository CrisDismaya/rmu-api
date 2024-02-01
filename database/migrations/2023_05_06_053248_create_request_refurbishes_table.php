<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestRefurbishesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_refurbishes', function (Blueprint $table) {
            $table->id();
            $table->BigInteger('repo_id');
            $table->BigInteger('branch');
            $table->BigInteger('maker');
            $table->BigInteger('approver')->nullable();
            $table->date('date_approved')->nullable();
            $table->json('files_names')->nullable();
			$table->json('paths')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status',['0','1','2','3','4'])->default('0'); //3 - pending for approval refurbish process, 4 -approved back to inventory
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
        Schema::dropIfExists('request_refurbishes');
    }
}
