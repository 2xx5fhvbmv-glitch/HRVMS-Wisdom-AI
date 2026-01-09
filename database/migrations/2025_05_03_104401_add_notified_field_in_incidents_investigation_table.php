<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotifiedFieldInIncidentsInvestigationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incidents_investigation', function (Blueprint $table) {
            $table->enum('police_notified',['yes','no','not_required'])->default('no')->after('created_by');
            $table->enum('mdf_notified',['yes','no','not_required'])->default('no')->after('police_time');
            $table->enum('fire_rescue_notified',['yes','no','not_required'])->default('no')->after('mndf_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incidents_investigation', function (Blueprint $table) {
            $table->dropColum('police_notified');
            $table->dropColum('mdf_notified');
            $table->dropColum('fire_rescue_notified');
        });
    }
}
