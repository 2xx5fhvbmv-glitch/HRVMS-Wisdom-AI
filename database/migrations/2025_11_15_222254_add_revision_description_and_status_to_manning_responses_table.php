<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevisionDescriptionAndStatusToManningResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manning_responses', function (Blueprint $table) {
            $table->string('budget_process_status')->nullable()->after('total_vacant_positions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manning_responses', function (Blueprint $table) {
            $table->dropColumn(['budget_process_status']);
        });
    }
}
