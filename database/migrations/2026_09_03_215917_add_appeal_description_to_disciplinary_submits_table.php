<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppealDescriptionToDisciplinarySubmitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disciplinary_submits', function (Blueprint $table) {
            // Mirrors Acknowledgment_description exactly — same table, same
            // shape, backing the new disciplinary/appeal-submit endpoint
            // (the mobile app already calls this route, it just never
            // existed server-side).
            $table->text('Appeal_description')->nullable()->after('Acknowledgment_description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disciplinary_submits', function (Blueprint $table) {
            $table->dropColumn('Appeal_description');
        });
    }
}
