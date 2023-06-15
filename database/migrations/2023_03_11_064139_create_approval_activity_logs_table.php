<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('module_id');
            $table->bigInteger('rec_id');
            $table->bigInteger('user_id');
            $table->integer('order');
            $table->enum('decision', ['N', 'A', 'D'])->nullable();
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
        Schema::dropIfExists('approval_activity_logs');
    }
}
