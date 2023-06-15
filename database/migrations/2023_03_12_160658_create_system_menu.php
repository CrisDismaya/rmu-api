<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_menu', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('category_name');
            $table->integer('parent_id');
            $table->string('menu_name');
            $table->string('file_path')->nullable();
            $table->enum('status', [0, 1])->default(1);
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
        Schema::dropIfExists('user_access');
    }
}
