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
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            //Optional link to a system login account — many employees (e.g. warehouse
            //staff) will never need one.
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->string('employee_code');
            $table->string('first_name');
            $table->string('last_name')->nullable();

            $table->integer('department_id')->unsigned()->nullable();
            $table->foreign('department_id')->references('id')->on('hr_departments')->onDelete('set null');

            $table->integer('designation_id')->unsigned()->nullable();
            $table->foreign('designation_id')->references('id')->on('hr_designations')->onDelete('set null');

            $table->integer('location_id')->unsigned()->nullable();
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('set null');

            $table->integer('reporting_to')->unsigned()->nullable();
            $table->foreign('reporting_to')->references('id')->on('employees')->onDelete('set null');

            $table->date('date_of_joining')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone')->nullable();
            $table->string('personal_email')->nullable();

            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('national_id_number')->nullable();

            $table->enum('status', ['active', 'on_leave', 'terminated', 'resigned'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'employee_code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
