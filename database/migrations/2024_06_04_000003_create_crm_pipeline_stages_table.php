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
        Schema::create('crm_pipeline_stages', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('crm_pipeline_id')->unsigned();
            $table->foreign('crm_pipeline_id')->references('id')->on('crm_pipelines')->onDelete('cascade');

            $table->string('name');
            $table->integer('stage_order')->default(1);
            $table->decimal('probability_percent', 5, 2)->default(0);
            $table->boolean('is_won')->default(0);
            $table->boolean('is_lost')->default(0);

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
        Schema::dropIfExists('crm_pipeline_stages');
    }
};
