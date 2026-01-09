<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildSosHistoryStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_sos_history_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sos_history_id');
            $table->enum('sos_status', ['sos_activation', 'manager_acknowledgement', 'team_notifications_sent','acknowledgements_received_from_team_members','chat_updates','situation_was_marked_as_under_control','sos_completed']);
            $table->timestamps();
            $table->foreign('sos_history_id')->references('id')->on('sos_history')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('child_sos_history_status');
    }
}
