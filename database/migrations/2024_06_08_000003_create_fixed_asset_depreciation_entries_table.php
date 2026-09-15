<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fixed_asset_depreciation_entries', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('fixed_asset_id')->unsigned();
            $table->foreign('fixed_asset_id', 'fade_asset_fk')->references('id')->on('fixed_assets')->onDelete('cascade');

            $table->date('depreciation_date');
            $table->decimal('amount', 22, 4);

            //Set only when Phase 3's GL is enabled for the business at posting time.
            $table->integer('journal_entry_id')->unsigned()->nullable();
            $table->foreign('journal_entry_id', 'fade_journal_fk')->references('id')->on('journal_entries')->onDelete('set null');

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->unique(['fixed_asset_id', 'depreciation_date'], 'fade_asset_date_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fixed_asset_depreciation_entries');
    }
};
