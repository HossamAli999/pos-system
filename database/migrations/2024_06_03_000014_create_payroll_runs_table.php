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
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->integer('location_id')->unsigned()->nullable();
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('set null');

            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->date('pay_date');

            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');

            $table->decimal('total_earnings', 22, 4)->default(0);
            $table->decimal('total_deductions', 22, 4)->default(0);
            $table->decimal('total_net_pay', 22, 4)->default(0);

            //Set once postPayrollRunToLedger() has posted this run's net pay to the
            //existing cash/bank ledger via a 'payroll' Transaction.
            $table->integer('transaction_id')->unsigned()->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->integer('approved_by')->unsigned()->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();

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
        Schema::dropIfExists('payroll_runs');
    }
};
