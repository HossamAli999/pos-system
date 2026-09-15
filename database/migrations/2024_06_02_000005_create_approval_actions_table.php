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
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('approval_request_id')->unsigned();
            $table->foreign('approval_request_id')->references('id')->on('approval_requests')->onDelete('cascade');

            $table->integer('approval_step_id')->unsigned();
            $table->foreign('approval_step_id')->references('id')->on('approval_steps')->onDelete('cascade');

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->enum('action', ['approved', 'rejected', 'delegated', 'commented']);
            $table->text('comment')->nullable();

            $table->dateTime('acted_at');

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
        Schema::dropIfExists('approval_actions');
    }
};
