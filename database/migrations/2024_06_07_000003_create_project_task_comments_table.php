<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('project_task_comments', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('project_task_id')->unsigned();
            $table->foreign('project_task_id', 'proj_task_comments_task_fk')->references('id')->on('project_tasks')->onDelete('cascade');

            $table->text('comment');

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_task_comments');
    }
};
