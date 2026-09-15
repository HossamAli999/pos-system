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
        Schema::create('employee_shift_assignments', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('employee_id')->unsigned();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->integer('attendance_shift_id')->unsigned();
            $table->foreign('attendance_shift_id')->references('id')->on('attendance_shifts')->onDelete('cascade');

            $table->date('effective_from');

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
        Schema::dropIfExists('employee_shift_assignments');
    }
};
