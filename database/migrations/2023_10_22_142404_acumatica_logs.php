<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AcumaticaLogs extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('acumatica_logs', function (Blueprint $table) {
			$table->bigInteger('id',true);
			$table->bigInteger('sold_units_id');
			$table->text('request')->nullable();
			$table->string('method')->nullable();
			$table->string('action')->nullable();
			$table->integer('status_code')->nullable();
			$table->text('parameter')->nullable();
			$table->text('response')->nullable();
			$table->integer('attempt')->default(0);
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
		Schema::dropIfExists('acumatica_logs');
	}
}
