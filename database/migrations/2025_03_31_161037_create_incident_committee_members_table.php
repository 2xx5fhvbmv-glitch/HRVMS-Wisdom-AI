<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentCommitteeMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incident_committee_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commitee_id');
            $table->unsignedInteger('member_id');
            $table->timestamps();

            $table->foreign('commitee_id')->references('id')->on('incident_committee')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('employees')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incident_committee_members');
    }
}
