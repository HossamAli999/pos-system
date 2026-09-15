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
        Schema::create('crm_lead_products', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('crm_lead_id')->unsigned();
            $table->foreign('crm_lead_id')->references('id')->on('crm_leads')->onDelete('cascade');

            //Deliberately not TransactionSellLine — these are pre-sales line items
            //that only become real sell lines when the lead is converted to a
            //quotation via App\Utils\CrmUtil::convertLeadToQuotation().
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->integer('variation_id')->unsigned();
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');

            $table->decimal('quantity', 22, 4)->default(1);
            $table->decimal('unit_price', 22, 4)->default(0);

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
        Schema::dropIfExists('crm_lead_products');
    }
};
