<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProgramTypeToColumnToTrainingAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('training_attendance', function (Blueprint $table) {
            $table->enum('program_type',['scheduled','mandatory','requested','probationary'])->default('scheduled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('training_attendance', function (Blueprint $table) {
            $table->dropColumn('program_type');
        });
    }
}
