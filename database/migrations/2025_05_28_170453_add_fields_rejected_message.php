<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsRejectedMessage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sos_history', function (Blueprint $table) {
            $table->date('sos_approved_date')->nullable()->after('sos_approved_time');
            $table->string('rejected_message')->nullable()->after('team_message');
            DB::statement("ALTER TABLE sos_history MODIFY COLUMN status ENUM('Completed','Active','Pending','Rejected','Real-Active','Under-Control','In-Progress','Drill-Active','Drill-Rejected','Drill-Completed','Drill-Under-Control') DEFAULT 'Pending'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sos_history', function (Blueprint $table) {
            $table->dropColumn('sos_approved_date');
            $table->dropColumn('rejected_message');
            DB::statement("ALTER TABLE sos_history MODIFY COLUMN status ENUM('Completed','Active','Drill-active','Pending','Rejected')");
        });
    }
}
