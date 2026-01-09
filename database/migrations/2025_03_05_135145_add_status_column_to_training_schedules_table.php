<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusColumnToTrainingSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('training_schedules', function (Blueprint $table) {
            $table->enum('status', ['Scheduled', 'Ongoing', 'Completed','Pending'])->default('Pending');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('training_schedules', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
