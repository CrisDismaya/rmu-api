<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryStockTransfer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('history_stock_transfer', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('stock_transfer_id');
            $table->integer('received_unit_id');
            $table->integer('from_branch');
            $table->integer('to_branch');
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
        Schema::dropIfExists('history_stock_transfer');
    }
}
