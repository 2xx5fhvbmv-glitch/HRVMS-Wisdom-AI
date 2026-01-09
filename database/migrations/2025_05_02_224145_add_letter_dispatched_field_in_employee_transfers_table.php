<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLetterDispatchedFieldInEmployeeTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            $table->enum('letter_dispatched',['Yes','No'])->default('No');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            $table->dropColumn('letter_dispatched');
        });
    }
}
