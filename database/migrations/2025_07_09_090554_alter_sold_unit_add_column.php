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
        Schema::table('sold_units', function (Blueprint $table) {
			$table->string('transaction_number')->nullable();
			$table->string('transaction_number_inventory_out')->nullable();
			$table->timestamp('inventory_out_at')->nullable();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sold_units', function (Blueprint $table) {
            $table->dropColumn('transaction_number');
            $table->dropColumn('transaction_number_inventory_out');
            $table->dropColumn('inventory_out_at');
        });
    }
};
