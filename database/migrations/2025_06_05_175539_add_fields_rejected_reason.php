<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsRejectedReason extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->string('rejected_reason')->nullable()->after('status');
            $table->string('withdraw_reason')->nullable()->after('rejected_reason');
            DB::statement("ALTER TABLE employee_resignation MODIFY status ENUM('Pending', 'Approved', 'Rejected','On Hold','Withdraw') NOT NULL DEFAULT 'Pending'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->dropColumn('rejected_reason');
            $table->dropColumn('withdraw_reason');

            DB::statement("ALTER TABLE employee_resignation MODIFY status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending'");
        });
    }
}
