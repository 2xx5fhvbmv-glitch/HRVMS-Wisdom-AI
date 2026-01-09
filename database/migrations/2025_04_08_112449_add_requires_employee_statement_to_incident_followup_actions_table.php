<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequiresEmployeeStatementToIncidentFollowupActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grivance_submission_witnesses', function (Blueprint $table) 
        {
            $table->enum('status',['Requested','Approved','NoAction'])->default('NoAction');
            $table->string('Statement')->default(null);
            $table->string('Attachement')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *           

     * @return void
     */
    public function down()
    {
        Schema::table('grivance_submission_witnesses', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('Statement');
            $table->dropColumn('Attachement');
        });
    }
}
