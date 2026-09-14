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
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('van_sales_trip_id')->unsigned()->nullable()->after('delivery_person');
            $table->foreign('van_sales_trip_id')->references('id')->on('van_sales_trips')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['van_sales_trip_id']);
            $table->dropColumn('van_sales_trip_id');
        });
    }
};
