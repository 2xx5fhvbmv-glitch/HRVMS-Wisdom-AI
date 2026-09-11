<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOutOfBudgetJustificationToVacanciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vacancies', function (Blueprint $table) {
            // Same status vocabulary as manning_responses.budget_process_status
            // (Finance/GM/Approved/Rejected) — null means either "within
            // budget, not applicable" or "over budget, not yet justified".
            $table->text('justification')->nullable()->after('is_required_local');
            $table->string('out_of_budget_status')->nullable()->after('justification');
            $table->text('out_of_budget_comment')->nullable()->after('out_of_budget_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn(['justification', 'out_of_budget_status', 'out_of_budget_comment']);
        });
    }
}
