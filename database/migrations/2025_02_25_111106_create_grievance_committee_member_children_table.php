<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceCommitteeMemberChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grievance_committee_member_children', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('resort_id');
                $table->unsignedBigInteger('Parent_id');
                $table->integer('Committee_Member_Id')->nullable();
                $table->timestamps();
                $table->foreign('Parent_id')->references('id')->on('grievance_committee_member_parents');
                $table->foreign('resort_id')->references('id')->on('resorts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grievance_committee_member_children');
    }
}
