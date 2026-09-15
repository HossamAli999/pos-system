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
        Schema::create('bom_items', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('bill_of_material_id')->unsigned();
            $table->foreign('bill_of_material_id')->references('id')->on('bill_of_materials')->onDelete('cascade');

            $table->integer('raw_material_product_id')->unsigned();
            $table->foreign('raw_material_product_id')->references('id')->on('products')->onDelete('cascade');

            $table->integer('raw_material_variation_id')->unsigned();
            $table->foreign('raw_material_variation_id')->references('id')->on('variations')->onDelete('cascade');

            $table->decimal('quantity_required', 22, 4);
            $table->decimal('wastage_percent', 8, 4)->default(0);

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
        Schema::dropIfExists('bom_items');
    }
};
