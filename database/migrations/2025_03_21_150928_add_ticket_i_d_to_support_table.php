<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTicketIDToSupportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('support', function (Blueprint $table) {
            $table->string('ticketID')->after('id');
            $table->string('support_preference')->after('resort_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('support', function (Blueprint $table) {
            $table->dropColumn('ticketID');
            $table->dropColumn('support_preference');
        });
    }
}
