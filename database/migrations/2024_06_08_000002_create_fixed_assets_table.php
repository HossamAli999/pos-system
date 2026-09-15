<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->integer('location_id')->unsigned()->nullable();
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('set null');

            $table->integer('fixed_asset_category_id')->unsigned()->nullable();
            $table->foreign('fixed_asset_category_id', 'fa_category_fk')->references('id')->on('fixed_asset_categories')->onDelete('set null');

            $table->string('name');
            $table->string('code')->nullable();

            $table->date('purchase_date');
            $table->decimal('purchase_cost', 22, 4);
            $table->decimal('salvage_value', 22, 4)->default(0);
            $table->integer('useful_life_months');
            $table->enum('depreciation_method', ['straight_line'])->default('straight_line');

            $table->decimal('accumulated_depreciation', 22, 4)->default(0);

            //Per-asset override of the category's default GL accounts.
            $table->integer('asset_account_id')->unsigned()->nullable();
            $table->foreign('asset_account_id', 'fa_asset_account_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');

            $table->integer('depreciation_account_id')->unsigned()->nullable();
            $table->foreign('depreciation_account_id', 'fa_depr_account_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');

            $table->integer('accumulated_depreciation_account_id')->unsigned()->nullable();
            $table->foreign('accumulated_depreciation_account_id', 'fa_accum_depr_account_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');

            $table->enum('status', ['active', 'disposed'])->default('active');
            $table->date('disposed_date')->nullable();
            $table->decimal('disposed_amount', 22, 4)->nullable();

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fixed_assets');
    }
};
