<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusEnumInClinicAppointmentModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clinic_appointment', function (Blueprint $table) {
            DB::statement("ALTER TABLE clinic_appointment MODIFY COLUMN status ENUM('Pending','Approved', 'Rejected','Reschedule','Cancel','Treatment','Medical Certificate') DEFAULT 'Pending'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clinic_appointment', function (Blueprint $table) {
            DB::statement("ALTER TABLE clinic_appointment MODIFY COLUMN status ENUM('Pending','Approved', 'Rejected','Reschedule','Cancel')");
        });
    }
}
