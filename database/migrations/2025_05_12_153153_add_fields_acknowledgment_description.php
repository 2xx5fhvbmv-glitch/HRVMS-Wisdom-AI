<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsAcknowledgmentDescription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disciplinary_submits', function (Blueprint $table) {
             $table->longText('Acknowledgment_description')->nullable()->after('Incident_description');
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
            $table->dropColumn('Acknowledgment_description');
        });
    }
}
