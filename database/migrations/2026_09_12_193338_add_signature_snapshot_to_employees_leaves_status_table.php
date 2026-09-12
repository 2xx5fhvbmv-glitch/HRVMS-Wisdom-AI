<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignatureSnapshotToEmployeesLeavesStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_leaves_status', function (Blueprint $table) {
            // Frozen at the moment of THIS approval — never re-derived from
            // ResortAdmin.signature_img later (Common::snapshotSignature()).
            // approved_at is only a DATE column already in use elsewhere;
            // signed_at keeps full date+time precision without touching it.
            $table->string('signature_img')->nullable()->after('approved_at');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees_leaves_status', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });
    }
}
