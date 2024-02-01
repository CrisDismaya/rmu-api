<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTransferUnitTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stock_transfer_unit', function (Blueprint $table) {
			$table->bigInteger('id', true);
			$table->integer('stock_transfer_id');
			$table->integer('recieved_unit_id');
			$table->enum('is_received', [ 0, 1, 2, 9 ])->default(0);
			$table->enum('is_use_old_files', [ 0, 1, 2, 9 ])->default(0);
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
		Schema::dropIfExists('stock_transfer_unit');
	}
}
