<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncidentsinvestigationFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incidents_investigation', function (Blueprint $table) {
            $table->enum('Ministry_notified', ['yes', 'no','not_required'])->default('no')->after('fire_rescue_time');
            $table->date('Ministry_notified_date')->nullable()->after('Ministry_notified');
            $table->time('Ministry_time')->nullable()->after('Ministry_notified_date');
        });
    }   

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incidents_investigation', function (Blueprint $table) 
        {
            $table->dropColumn('Ministry_notified');
            $table->dropColumn('Ministry_notified_date');
        });
    }
}
