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
        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('employee_id')->unsigned();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            //A new row is created for every change instead of editing in place, so
            //past payroll runs keep referencing the structure that was actually in
            //effect when they were generated.
            $table->date('effective_from');
            $table->decimal('basic_salary', 22, 4)->default(0);
            $table->enum('pay_frequency', ['monthly', 'biweekly', 'weekly'])->default('monthly');

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_salary_structures');
    }
};
