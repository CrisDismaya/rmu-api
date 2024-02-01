<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();
            $table->BigInteger('received_unit_id');
            $table->BigInteger('repo_id');
            $table->BigInteger('branch');
            $table->integer('unit_age_days');
            $table->float('depreciation_cost');
            $table->float('estimated_missing_dmg_parts');
            $table->float('total_missing_dmg_parts');
            $table->float('suggested_price');
            $table->float('approved_price')->nullable();
            $table->float('edited_price')->nullable();
            $table->BigInteger('approver')->nullable();
            $table->date('date_approved')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status', [0, 1, 2])->default(0);
            $table->BigInteger('created_by');
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
        Schema::dropIfExists('request_approvals');
    }
}
