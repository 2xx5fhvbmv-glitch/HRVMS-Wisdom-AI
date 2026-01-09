<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBudgetStatusFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('budget_statuses', function (Blueprint $table) {
            // Check if 'Department_id' column does not exist and then add it
            if (!Schema::hasColumn('budget_statuses', 'Department_id')) {
                $table->integer('Department_id')->after('resort_id');
            }

            // Add 'OtherComments' column if it does not already exist
            if (!Schema::hasColumn('budget_statuses', 'OtherComments')) {
                $table->text('OtherComments')->after('comments');
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
        Schema::table('budget_statuses', function (Blueprint $table) {
            // Remove the 'Department_id' column only if it exists
            if (Schema::hasColumn('budget_statuses', 'Department_id')) {
                $table->dropColumn('Department_id');
            }

            // Always remove the 'OtherComments' column if it exists
            if (Schema::hasColumn('budget_statuses', 'OtherComments')) {
                $table->dropColumn('OtherComments');
            }
        });
    }
}
