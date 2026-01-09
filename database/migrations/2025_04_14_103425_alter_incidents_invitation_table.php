<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterIncidentsInvitationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incidents_investigation', function (Blueprint $table) {
            $table->dropColumn('outcome_type');
            $table->dropColumn('action_taken');

            $table->unsignedBigInteger('folloup_action')->nullable()->after('investigation_findings');

            $table->foreign('folloup_action')->references('id')->on('incident_followup_actions')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incidents_investigation', function (Blueprint $table) {
            $table->dropForeign(['folloup_action']);
            $table->dropColumn('folloup_action');

            $table->unsignedBigInteger('outcome_type')->nullable()->after('investigation_findings');
            $table->unsignedBigInteger('action_taken')->nullable()->after('outcome_type');

            $table->foreign('outcome_type')->references('id')->on('incident_outcome_types')->onDelete('cascade');
            $table->foreign('action_taken')->references('id')->on('incident_actions_taken')->onDelete('cascade');

        });

    }
}
