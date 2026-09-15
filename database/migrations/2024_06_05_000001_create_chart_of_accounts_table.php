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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->string('code')->nullable();
            $table->string('name');
            $table->enum('account_category', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->string('account_subcategory')->nullable();

            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('id')->on('chart_of_accounts')->onDelete('set null');

            //Links this GL account 1:1 to an existing cash/bank "payment account" row
            //(the app/Account.php model) so both ledgers stay reconcilable.
            $table->integer('linked_account_id')->unsigned()->nullable();
            $table->foreign('linked_account_id')->references('id')->on('accounts')->onDelete('set null');

            $table->boolean('is_system')->default(0);
            $table->boolean('is_active')->default(1);

            $table->decimal('opening_balance', 22, 4)->default(0);
            $table->date('opening_balance_date')->nullable();

            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
