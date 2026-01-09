<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeestatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'status')) {
                $table->dropColumn('status');
            }
            if(!DB::getSchemaBuilder()->hasColumn('employees', 'status'))
            {
                $table->enum('status', [

                    'Active','OnLeave','Probationary','Terminated','Inactive','Retired','Resigned','Suspended','transferred','contractual'
                ])->default('Active');
            }


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
