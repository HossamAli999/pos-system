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
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('journal_entry_id')->unsigned();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');

            $table->integer('chart_of_account_id')->unsigned();
            $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');

            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);

            //For AR/AP drill-down by customer/supplier and per-product margin analysis.
            $table->integer('contact_id')->unsigned()->nullable();
            $table->integer('product_id')->unsigned()->nullable();

            $table->string('memo')->nullable();

            $table->timestamps();

            $table->index('chart_of_account_id');
            $table->index('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('journal_lines');
    }
};
