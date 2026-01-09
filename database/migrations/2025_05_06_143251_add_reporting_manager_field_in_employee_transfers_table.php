<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReportingManagerFieldInEmployeeTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            $table->unsignedInteger('reporting_manager')->nullable()->after('additional_notes');
            $table->integer('created_by');
            $table->integer('modified_by');
        
            $table->foreign('reporting_manager')
                  ->references('id')
                  ->on('employees')
                  ->onDelete('set null'); // Optional: prevents cascade delete of transfers if manager is deleted
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            $table->dropForeign(['reporting_manager']);
            $table->dropColumn('reporting_manager');
            $table->dropColumn('created_by');
            $table->dropColumn('modified_by');
        });
    }
}
