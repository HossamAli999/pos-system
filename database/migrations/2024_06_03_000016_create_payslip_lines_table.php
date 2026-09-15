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
        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('payslip_id')->unsigned();
            $table->foreign('payslip_id')->references('id')->on('payslips')->onDelete('cascade');

            $table->integer('payroll_component_id')->unsigned()->nullable();
            $table->foreign('payroll_component_id')->references('id')->on('payroll_components')->onDelete('set null');

            //Snapshot of the component name at generation time, so a payslip stays
            //readable even if the component is later renamed or deleted.
            $table->string('component_name');
            $table->enum('type', ['earning', 'deduction']);
            $table->decimal('amount', 22, 4)->default(0);

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
        Schema::dropIfExists('payslip_lines');
    }
};
