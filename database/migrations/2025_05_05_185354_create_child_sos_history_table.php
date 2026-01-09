<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildSosHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_sos_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sos_history_id');
            $table->unsignedBigInteger('team_id');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sos_history_id')->references('id')->on('sos_history')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('sos_teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('child_sos_history');
    }
}
