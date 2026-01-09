<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmpIdForeignOnSosTeamMemberActivityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sos_team_member_activity', function (Blueprint $table) {
            // Drop the old foreign key
            $table->dropForeign(['emp_id']);

            // Add the new foreign key referencing resort_admins
            $table->foreign('emp_id')
                ->references('id')
                ->on('resort_admins')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sos_team_member_activity', function (Blueprint $table) {
            $table->dropForeign(['emp_id']);

            $table->foreign('emp_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');
        });
    }
}
