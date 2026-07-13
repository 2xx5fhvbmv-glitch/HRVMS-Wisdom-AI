<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeofenceFieldsToChildAttendacesTable extends Migration
{
    public function up()
    {
        Schema::table('child_attendaces', function (Blueprint $table) {
            $table->decimal('InTime_Latitude', 10, 7)->nullable()->after('InTime_Location');
            $table->decimal('InTime_Longitude', 10, 7)->nullable()->after('InTime_Latitude');
            $table->decimal('InTime_Accuracy', 8, 2)->nullable()->after('InTime_Longitude');
            $table->boolean('InTime_WithinGeofence')->nullable()->after('InTime_Accuracy');

            $table->decimal('OutTime_Latitude', 10, 7)->nullable()->after('OutTime_Location');
            $table->decimal('OutTime_Longitude', 10, 7)->nullable()->after('OutTime_Latitude');
            $table->decimal('OutTime_Accuracy', 8, 2)->nullable()->after('OutTime_Longitude');
            $table->boolean('OutTime_WithinGeofence')->nullable()->after('OutTime_Accuracy');
        });
    }

    public function down()
    {
        Schema::table('child_attendaces', function (Blueprint $table) {
            $table->dropColumn([
                'InTime_Latitude', 'InTime_Longitude', 'InTime_Accuracy', 'InTime_WithinGeofence',
                'OutTime_Latitude', 'OutTime_Longitude', 'OutTime_Accuracy', 'OutTime_WithinGeofence',
            ]);
        });
    }
}
