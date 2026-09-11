<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddEmploymentTypeToManningResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manning_responses', function (Blueprint $table) {
            $table->string('employment_type')->default('Permanent')->after('year');
        });

        // Belt-and-suspenders backfill for any row that predates the
        // default (shouldn't exist given the column is new, but matches
        // the plan's explicit instruction and costs nothing).
        DB::table('manning_responses')->whereNull('employment_type')->update(['employment_type' => 'Permanent']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manning_responses', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });
    }
}
