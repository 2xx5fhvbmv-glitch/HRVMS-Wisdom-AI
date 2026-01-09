<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewfieldAtNotification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('t_anotification_children', function (Blueprint $table)
        {
            $table->text('reason')->nullable()->after('holding_date');
            $table->text('Approved_By')->nullable()->after('reason');
        });

        Schema::table('job_advertisements', function (Blueprint $table)
        {
            $table->integer('FinalApproval')->nullable()->after('Jobadvimg');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('t_anotification_children', function (Blueprint $table)
        {
            $table->dropColumn('reason');
            $table->dropColumn('Approved_By');

        });

        Schema::table('job_advertisements', function (Blueprint $table)
        {
            $table->dropColumn('FinalApproval');

        });

    }
}
