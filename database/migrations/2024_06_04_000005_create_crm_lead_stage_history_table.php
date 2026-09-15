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
        Schema::create('crm_lead_stage_history', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('crm_lead_id')->unsigned();
            $table->foreign('crm_lead_id')->references('id')->on('crm_leads')->onDelete('cascade');

            $table->integer('from_stage_id')->unsigned()->nullable();
            $table->foreign('from_stage_id')->references('id')->on('crm_pipeline_stages')->onDelete('set null');

            $table->integer('to_stage_id')->unsigned();
            $table->foreign('to_stage_id')->references('id')->on('crm_pipeline_stages')->onDelete('cascade');

            $table->integer('changed_by')->unsigned()->nullable();
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');

            $table->dateTime('changed_at');

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
        Schema::dropIfExists('crm_lead_stage_history');
    }
};
