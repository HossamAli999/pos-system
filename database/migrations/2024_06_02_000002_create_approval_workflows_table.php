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
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            //Module this workflow applies to, e.g. 'leave_request', 'purchase_requisition'
            $table->string('module');
            $table->string('name');

            //Optional amount range the workflow applies to (null = applies to all amounts)
            $table->decimal('min_amount', 22, 4)->nullable();
            $table->decimal('max_amount', 22, 4)->nullable();

            $table->boolean('is_active')->default(1);

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

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
        Schema::dropIfExists('approval_workflows');
    }
};
