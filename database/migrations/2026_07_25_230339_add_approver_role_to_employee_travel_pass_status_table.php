<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApproverRoleToEmployeeTravelPassStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_travel_pass_status', function (Blueprint $table) {
            // The functional stage this row represents in the approval
            // chain (HOD/HR/SM/GM) — NOT the same thing as approver_rank,
            // which is the approver's own personal organizational rank.
            // Both an HR-department head and a Security Manager can
            // personally hold rank=2 ("HOD"), so labeling the stage from
            // approver_rank mislabeled their rows as "HOD" too.
            $table->string('approver_role', 20)->nullable()->after('approver_rank');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_travel_pass_status', function (Blueprint $table) {
            $table->dropColumn('approver_role');
        });
    }
}
