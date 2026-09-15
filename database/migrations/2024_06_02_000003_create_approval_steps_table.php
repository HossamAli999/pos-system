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
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('approval_workflow_id')->unsigned();
            $table->foreign('approval_workflow_id')->references('id')->on('approval_workflows')->onDelete('cascade');

            $table->integer('step_order')->default(1);

            //Who can act on this step
            $table->enum('approver_type', ['user', 'role', 'permission']);
            $table->integer('approver_id')->unsigned()->nullable(); //user id or role id, depending on approver_type
            $table->string('permission_name')->nullable(); //used when approver_type = 'permission'

            $table->boolean('is_final')->default(0);

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
        Schema::dropIfExists('approval_steps');
    }
};
