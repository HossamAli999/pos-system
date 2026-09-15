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
        Schema::create('salary_structure_components', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('salary_structure_id')->unsigned();
            $table->foreign('salary_structure_id')->references('id')->on('employee_salary_structures')->onDelete('cascade');

            $table->integer('payroll_component_id')->unsigned();
            $table->foreign('payroll_component_id')->references('id')->on('payroll_components')->onDelete('cascade');

            $table->enum('calc_type', ['fixed', 'percentage_of_basic'])->default('fixed');
            $table->decimal('amount', 22, 4)->nullable();
            $table->decimal('percentage', 8, 4)->nullable();

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
        Schema::dropIfExists('salary_structure_components');
    }
};
