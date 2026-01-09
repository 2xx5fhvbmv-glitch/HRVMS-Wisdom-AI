<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvestigationformFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->string('outcome_type')->after('SentToGM')->nullable();
            $table->string('action_taken')->after('outcome_type')->nullable();
            $table->string('Request_Identity_Disclosure')->after('action_taken')->nullable();
            $table->enum('Gm_Decision',['Approved','Rejacted'])->after('Request_Identity_Disclosure')->nullable();
            $table->text('Rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       
        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->dropColumn('outcome_type');
            $table->dropColumn('action_taken');
            $table->dropColumn('Request_Identity_Disclosure');
            $table->dropColumn('Gm_Decision');
            $table->dropColumn('Rejection_reason');
            
        });
    }
}
