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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->integer('employee_id')->unsigned();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->integer('leave_type_id')->unsigned();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');

            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_requested', 8, 2);
            $table->text('reason')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            //Set when Phase 0's generic approval engine has a workflow configured
            //for the 'leave_request' module; left null otherwise, in which case
            //LeaveUtil decides approve/reject directly against this status column.
            $table->integer('approval_request_id')->unsigned()->nullable();
            $table->foreign('approval_request_id')->references('id')->on('approval_requests')->onDelete('set null');

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->integer('decided_by')->unsigned()->nullable();
            $table->foreign('decided_by')->references('id')->on('users')->onDelete('set null');
            $table->dateTime('decided_at')->nullable();

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
        Schema::dropIfExists('leave_requests');
    }
};
