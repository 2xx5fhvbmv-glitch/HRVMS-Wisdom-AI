<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatementFileColumnToIncidentsWitnessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incidents_witness', function (Blueprint $table) {
            $table->text('witness_statement_file')->nullable()->after('witness_statements');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incidents_witness', function (Blueprint $table) {
            $table->dropColumn('witness_statement_file');
        });
    }
}
