<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsMassInstructions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sos_history', function (Blueprint $table) {
            $table->string('mass_instructions')->nullable()->after('rejected_message');
            DB::statement("ALTER TABLE sos_history MODIFY COLUMN status ENUM('Completed','Active','Pending','Rejected','Real-Active','In-Progress','Drill-Active','Drill-Rejected','Drill-Completed') DEFAULT 'Pending'");
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
            $table->dropColumn('mass_instructions');
            DB::statement("ALTER TABLE sos_history MODIFY COLUMN status ENUM('Completed','Active','Pending','Rejected','Real-Active','In-Progress','Drill-Active','Drill-Rejected','Drill-Completed') DEFAULT 'Pending'");
        });
    }
}
