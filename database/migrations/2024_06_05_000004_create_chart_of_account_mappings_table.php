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
        Schema::create('chart_of_account_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            //e.g. sales_income, inventory_asset, cogs_expense, accounts_receivable,
            //accounts_payable, sales_tax_payable, payroll_expense, payroll_payable —
            //the rulebook App\Utils\JournalUtil reads instead of hardcoding account ids.
            $table->string('mapping_key');

            $table->integer('chart_of_account_id')->unsigned();
            $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');

            $table->timestamps();

            $table->unique(['business_id', 'mapping_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chart_of_account_mappings');
    }
};
