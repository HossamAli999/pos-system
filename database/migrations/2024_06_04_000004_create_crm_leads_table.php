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
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->string('lead_number')->nullable();

            //Set once the lead is converted to a real customer contact — the lead
            //itself is never a Contact before that (see design note in the plan:
            //we don't add a 'lead' value to contacts.type).
            $table->integer('contact_id')->unsigned()->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');

            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->integer('source_id')->unsigned()->nullable();
            $table->foreign('source_id')->references('id')->on('crm_lead_sources')->onDelete('set null');

            $table->integer('assigned_to')->unsigned()->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            $table->integer('pipeline_id')->unsigned();
            $table->foreign('pipeline_id')->references('id')->on('crm_pipelines')->onDelete('cascade');

            $table->integer('stage_id')->unsigned();
            $table->foreign('stage_id')->references('id')->on('crm_pipeline_stages')->onDelete('cascade');

            $table->decimal('expected_value', 22, 4)->nullable();
            $table->date('expected_close_date')->nullable();

            $table->enum('status', ['open', 'won', 'lost'])->default('open');
            $table->text('lost_reason')->nullable();

            $table->integer('converted_contact_id')->unsigned()->nullable();
            $table->foreign('converted_contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->dateTime('converted_at')->nullable();

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_leads');
    }
};
