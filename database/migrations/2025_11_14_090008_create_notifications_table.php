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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branchs')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('system_menu')->onDelete('cascade');
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_viewed')->default(false);
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('user_notifications');
    }
};
