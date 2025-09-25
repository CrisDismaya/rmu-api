<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recieve_unit_spare_parts', function (Blueprint $table) {
			$table->string('dir_image')->nullable();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recieve_unit_spare_parts', function (Blueprint $table) {
            $table->dropColumn('dir_image');
        });
    }
};
