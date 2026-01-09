<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicationLnikFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('application_links', 'Old_ExpiryDate')) 
        {

            Schema::table('application_links', function (Blueprint $table) {
                $table->date('Old_ExpiryDate')->after('link_Expiry_date');
            });
        }
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('application_links', 'Old_ExpiryDate')) 
        {
            Schema::table('application_links', function (Blueprint $table) {
                $table->dropColumn('Old_ExpiryDate');
            });
        }
    }
}
