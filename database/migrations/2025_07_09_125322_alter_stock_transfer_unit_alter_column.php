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
        Schema::table('stock_transfer_unit', function (Blueprint $table) {
			$table->string('transaction_number_inventory_out')->nullable();
			$table->dateTime('inventory_out_at')->nullable();
			$table->string('trans_no_received')->nullable();
			$table->dateTime('received_at')->nullable();
			$table->string('transaction_number_inventory_in')->nullable();
			$table->dateTime('inventory_in_at')->nullable();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_transfer_unit', function (Blueprint $table) {
            $table->dropColumn('transaction_number_inventory_out');
            $table->dropColumn('inventory_out_at');
            $table->dropColumn('trans_no_received');
            $table->dropColumn('received_at');
            $table->dropColumn('transaction_number_inventory_in');
            $table->dropColumn('inventory_in_at');
        });
    }
};
