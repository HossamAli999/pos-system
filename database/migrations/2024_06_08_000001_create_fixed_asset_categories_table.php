<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fixed_asset_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->string('name');

            //Default GL accounts for assets in this category — each asset may
            //override these individually. Nullable throughout: fixed assets can
            //be tracked here even before Phase 3's GL is set up.
            $table->integer('default_asset_account_id')->unsigned()->nullable();
            $table->foreign('default_asset_account_id', 'fac_asset_account_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');

            $table->integer('default_depreciation_account_id')->unsigned()->nullable();
            $table->foreign('default_depreciation_account_id', 'fac_depr_account_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');

            $table->integer('default_accumulated_depreciation_account_id')->unsigned()->nullable();
            $table->foreign('default_accumulated_depreciation_account_id', 'fac_accum_depr_account_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fixed_asset_categories');
    }
};
