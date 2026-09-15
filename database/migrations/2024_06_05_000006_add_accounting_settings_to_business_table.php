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
        Schema::table('business', function (Blueprint $table) {
            //JSON-encoded settings, read as business.accounting_settings.enable_double_entry_accounting
            //by App\Utils\JournalUtil's listeners — null/absent for every existing business means
            //the new GL stays fully inert until a business owner explicitly opts in.
            $table->text('accounting_settings')->nullable()->after('accounting_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('accounting_settings');
        });
    }
};
