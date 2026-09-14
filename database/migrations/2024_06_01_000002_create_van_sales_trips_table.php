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
        Schema::create('van_sales_trips', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->integer('van_sales_vehicle_id')->unsigned();
            $table->foreign('van_sales_vehicle_id')->references('id')->on('van_sales_vehicles')->onDelete('cascade');

            $table->integer('rep_id')->unsigned();
            $table->foreign('rep_id')->references('id')->on('users')->onDelete('cascade');

            $table->integer('source_location_id')->unsigned();
            $table->foreign('source_location_id')->references('id')->on('business_locations')->onDelete('cascade');

            $table->integer('cash_register_id')->unsigned()->nullable();
            $table->foreign('cash_register_id')->references('id')->on('cash_registers')->onDelete('set null');

            $table->integer('transfer_out_id')->unsigned()->nullable();
            $table->foreign('transfer_out_id')->references('id')->on('transactions')->onDelete('set null');

            $table->integer('transfer_in_id')->unsigned()->nullable();
            $table->foreign('transfer_in_id')->references('id')->on('transactions')->onDelete('set null');

            $table->enum('status', ['out', 'pending_approval', 'closed', 'rejected'])->default('out');

            $table->decimal('opening_cash', 22, 4)->default(0);
            $table->decimal('proposed_closing_cash', 22, 4)->nullable();
            $table->decimal('closing_cash', 22, 4)->nullable();

            $table->dateTime('submitted_for_approval_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->integer('approved_by')->unsigned()->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->text('rep_note')->nullable();
            $table->text('manager_note')->nullable();

            $table->dateTime('started_at')->nullable();
            $table->dateTime('closed_at')->nullable();

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
        Schema::dropIfExists('van_sales_trips');
    }
};
