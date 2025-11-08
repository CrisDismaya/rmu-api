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
        Schema::create('physical_inventory_docs', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->date('selected_date');

            $table->integer('status')->default(0); // 0 = pending, 1 = approved, 2 = rejected
            $table->string('approver')->nullable();
            $table->string('approved_date')->nullable();
            $table->string('reason')->nullable();
            $table->string('remarks')->nullable();

            $table->integer('created_by');
            $table->timestamps();
            $table->integer('deleted_by')->nullable();
            $table->integer('is_deleted')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('physical_inventory_docs');
    }
};
