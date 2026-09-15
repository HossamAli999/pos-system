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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->integer('approval_workflow_id')->unsigned();
            $table->foreign('approval_workflow_id')->references('id')->on('approval_workflows')->onDelete('cascade');

            //Polymorphic link to the record awaiting approval (LeaveRequest, PurchaseRequisition transaction, ...)
            $table->string('approvable_type');
            $table->integer('approvable_id')->unsigned();

            $table->integer('current_step_id')->unsigned()->nullable();
            $table->foreign('current_step_id')->references('id')->on('approval_steps')->onDelete('set null');

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            $table->integer('requested_by')->unsigned();
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');

            $table->dateTime('requested_at');
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_requests');
    }
};
