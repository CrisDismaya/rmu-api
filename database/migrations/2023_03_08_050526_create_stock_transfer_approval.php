<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTransferApproval extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_transfer_approval', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->BigInteger('from_branch');
            $table->BigInteger('to_branch');
            $table->string('reference_code')->nullable();
            $table->BigInteger('approver')->nullable();
            $table->date('date_approved')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status', [0, 1, 2])->default(0);
            $table->BigInteger('created_by');
            $table->string('reason_for_transfer')->nullable();
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
        Schema::dropIfExists('stock_transfer_approval');
    }
}
