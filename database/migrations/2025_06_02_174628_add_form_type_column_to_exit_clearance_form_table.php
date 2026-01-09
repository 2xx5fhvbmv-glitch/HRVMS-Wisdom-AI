<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormTypeColumnToExitClearanceFormTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exit_clearance_form', function (Blueprint $table) {
            $table->enum('form_type',['department','employee'])
                ->default('department')
                ->after('department_id');
            $table->unsignedInteger('department_id')->nullable()->change();
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
            $table->dropColumn('form_type');
            $table->unsignedInteger('department_id')->change();
        });
    }
}
