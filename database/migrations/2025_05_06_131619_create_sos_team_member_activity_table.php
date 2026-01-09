<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSosTeamMemberActivityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sos_team_member_activity', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sos_history_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedInteger('emp_id');
            $table->string('location');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->enum('status',['Acknowledged','Unacknowledged'])->default('Unacknowledged');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sos_history_id')->references('id')->on('sos_history')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('sos_teams')->onDelete('cascade');
            $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sos_team_member_activity');
    }
}
