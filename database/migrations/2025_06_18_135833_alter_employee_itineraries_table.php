<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEmployeeItinerariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_itineraries', function (Blueprint $table) {
            // Make existing columns nullable
            $table->date('domestic_flight_date')->nullable()->change();
            $table->time('domestic_departure_time')->nullable()->change();
            $table->time('domestic_arrival_time')->nullable()->change();
            $table->text('domestic_flight_ticket')->nullable()->after('domestic_arrival_time');


            $table->string('speedboat_name')->nullable()->change();
            $table->string('captain_number')->nullable()->change();
            $table->string('location')->nullable()->change();

            // Add new speedboat fields
            $table->date('speedboat_date')->nullable()->after('speedboat_name');
            $table->time('speedboat_departure_time')->nullable()->after('speedboat_date');
            $table->time('speedboat_arrival_time')->nullable()->after('speedboat_departure_time');

            // Add new seaplane fields
            $table->string('seaplane_name')->nullable()->after('location');
            $table->date('seaplane_date')->nullable()->after('seaplane_name');
            $table->time('seaplane_departure_time')->nullable()->after('seaplane_date');
            $table->time('seaplane_arrival_time')->nullable()->after('seaplane_departure_time');

            // Add medical date and time
            $table->date('medical_date')->nullable()->after('medical_type');
            $table->time('medical_time')->nullable()->after('medical_date');

            // Change resort_transportation from string to foreign key
            $table->dropColumn('resort_transportation');
            $table->unsignedBigInteger('resort_transportation_id')->nullable()->after('accompany_medical_employee_id');

            // Add foreign key constraint
            $table->foreign('resort_transportation_id')
                  ->references('id')->on('resort_transportations')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_itineraries', function (Blueprint $table) {
            // Revert nullable changes (if needed) - optional
            $table->date('domestic_flight_date')->nullable(false)->change();
            $table->time('domestic_departur e_time')->nullable(false)->change();
            $table->time('domestic_arrival_time')->nullable(false)->change();

            $table->string('speedboat_name')->nullable(false)->change();
            $table->string('captain_number')->nullable(false)->change();
            $table->string('location')->nullable(false)->change();

            $table->dropForeign(['resort_transportation_id']);

            // Drop newly added columns
            $table->dropColumn([
                'domestic_flight_ticket',
                'speedboat_date',
                'speedboat_departure_time',
                'speedboat_arrival_time',
                'seaplane_date',
                'seaplane_departure_time',
                'seaplane_arrival_time',
                'medical_date',
                'medical_time',
                'resort_transportation_id'
            ]);

            // Restore original resort_transportation column
            $table->string('resort_transportation')->after('template_id');
        });
    }
}
