<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesUploadedTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('files_uploaded', function (Blueprint $table) {
			$table->bigInteger('id', true);
			$table->integer('module_id');
			$table->integer('reference_id');
			$table->integer('files_id');
			$table->string('files_name');
			$table->string('path');
			$table->enum('is_deleted', [0, 1])->default(0);
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
		Schema::dropIfExists('files_uploaded');
	}
}
