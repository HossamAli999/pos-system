<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('project_time_logs', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('project_task_id')->unsigned();
            $table->foreign('project_task_id', 'proj_time_logs_task_fk')->references('id')->on('project_tasks')->onDelete('cascade');

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->date('log_date');
            $table->decimal('hours', 8, 2);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_time_logs');
    }
};
