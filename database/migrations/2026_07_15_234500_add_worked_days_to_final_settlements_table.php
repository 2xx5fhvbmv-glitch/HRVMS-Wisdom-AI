<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkedDaysToFinalSettlementsTable extends Migration
{
    /**
     * The review page's "Earned Salary (Basic Salary for N day(s))" row
     * showed a day count recomputed live from current attendance, while
     * the dollar amount came from the frozen total_earnings column — on
     * an already-finalized settlement these can drift apart (attendance
     * data changes after the fact) producing nonsense like "0 days" next
     * to a non-zero paid amount. Freezing worked_days alongside the
     * other totals at store/submit time keeps the label and the amount
     * showing the same point-in-time snapshot.
     */
    public function up()
    {
        Schema::table('final_settlements', function (Blueprint $table) {
            $table->integer('worked_days')->nullable()->after('basic_salary');
        });
    }

    public function down()
    {
        Schema::table('final_settlements', function (Blueprint $table) {
            $table->dropColumn('worked_days');
        });
    }
}
