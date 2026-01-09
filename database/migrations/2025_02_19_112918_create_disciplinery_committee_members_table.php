<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplineryCommitteeMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disciplinery_committee_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_committee_id');
            $table->integer('MemberId')->nullable();
            $table->timestamps();
            $table->foreign('Parent_committee_id')->references('id')->on('disciplinery_assign_committees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disciplinery_committee_members');
    }
}
