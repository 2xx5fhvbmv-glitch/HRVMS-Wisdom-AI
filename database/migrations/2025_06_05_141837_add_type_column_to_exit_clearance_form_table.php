<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeColumnToExitClearanceFormTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exit_clearance_form', function (Blueprint $table) {
            $table->string('type')->nullable()->after('form_name')->comment('Type of exit clearance form');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exit_clearance_form', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
