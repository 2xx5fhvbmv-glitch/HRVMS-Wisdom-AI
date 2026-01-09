<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalRankToEmployeeTransfersApprovalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_transfers_approval', function (Blueprint $table) {
            $table->enum('approval_rank', ['Finance','GM'])->after('status')->comment('Approval rank for the transfer request');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_transfers_approval', function (Blueprint $table) {
            $table->dropColumn('approval_rank');
        });
    }
}
