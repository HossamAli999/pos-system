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
        Schema::create('payslips', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('payroll_run_id')->unsigned();
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->onDelete('cascade');

            $table->integer('employee_id')->unsigned();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->decimal('basic_salary', 22, 4)->default(0);
            $table->decimal('gross_earnings', 22, 4)->default(0);
            $table->decimal('total_deductions', 22, 4)->default(0);
            $table->decimal('net_pay', 22, 4)->default(0);

            //Days in the pay period the employee was absent/on unpaid leave —
            //kept for payslip transparency even though it already factored into
            //the amounts above at generation time.
            $table->decimal('unpaid_days', 8, 2)->default(0);

            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');

            $table->integer('payment_account_id')->unsigned()->nullable();
            $table->foreign('payment_account_id')->references('id')->on('accounts')->onDelete('set null');

            $table->dateTime('paid_on')->nullable();
            $table->string('pdf_path')->nullable();

            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payslips');
    }
};
