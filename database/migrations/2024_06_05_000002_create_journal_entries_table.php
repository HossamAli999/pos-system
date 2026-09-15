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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->integer('location_id')->unsigned()->nullable();
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('set null');

            $table->date('entry_date');
            $table->string('reference_number')->nullable();

            $table->enum('entry_type', [
                'sell', 'purchase', 'payment', 'expense', 'payroll', 'stock_adjustment',
                'manual', 'opening_balance', 'fixed_asset', 'depreciation', 'fx_revaluation',
            ]);

            //Polymorphic link back to the record that caused this entry (a Transaction,
            //PayrollRun, ...) — nullable for manual/opening-balance entries.
            $table->string('source_type')->nullable();
            $table->integer('source_id')->unsigned()->nullable();

            $table->text('narration')->nullable();
            $table->boolean('is_posted')->default(1);

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('journal_entries');
    }
};
