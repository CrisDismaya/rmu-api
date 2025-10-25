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
			$table->string('pt_receipt_no')->nullable();
			$table->date('pt_date')->nullable();
			$table->string('pt_bank')->nullable();
			$table->float('pt_amount')->nullable();
			$table->string('pt_uploads')->nullable();
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
            $table->dropColumn('pt_receipt_no');
            $table->dropColumn('pt_date');
            $table->dropColumn('pt_bank');
            $table->dropColumn('pt_amount');
            $table->dropColumn('pt_uploads');
        });
    }
};
