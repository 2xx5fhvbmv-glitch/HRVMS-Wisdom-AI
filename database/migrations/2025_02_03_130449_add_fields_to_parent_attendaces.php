<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToParentAttendaces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parent_attendaces', function (Blueprint $table) {
            $table->enum('CheckInCheckOut_Type', ['Manual', 'Geofencing', 'Biometric'])->comment('Allowed values: Manual, Geofencing, Biometric')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parent_attendaces', function (Blueprint $table) {
            $table->dropColumn('CheckInCheckOut_Type');
        });
    }
}
