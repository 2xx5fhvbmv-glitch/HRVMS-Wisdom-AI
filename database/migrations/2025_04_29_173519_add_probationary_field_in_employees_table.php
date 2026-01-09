<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProbationaryFieldInEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('probation_status', ['Active', 'Extended', 'Confirmed', 'Failed'])->default('Active')->after('probation_end_date');
            $table->date('probation_review_date')->nullable()->after('probation_status');
            $table->unsignedInteger('probation_confirmed_by')->nullable()->after('probation_review_date'); // FK to HR or manager
            $table->text('probation_remarks')->nullable()->after('probation_confirmed_by');
            $table->date('confirmation_date')->nullable()->after('probation_remarks');

            $table->foreign('probation_confirmed_by')->references('id')->on('employees')->onDelete('set null');

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
            // Drop foreign key first if it exists
            $table->dropForeign(['probation_confirmed_by']);
    
            // Now drop the columns
            $table->dropColumn('probation_status');
            $table->dropColumn('probation_review_date');
            $table->dropColumn('probation_confirmed_by');
            $table->dropColumn('probation_remarks');
            $table->dropColumn('confirmation_date');
        });
    }
    
}
