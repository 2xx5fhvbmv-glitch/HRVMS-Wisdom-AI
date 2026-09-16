<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frozen at the moment THIS approver acts (Common::snapshotSignature()) —
 * never re-derived later from the live ResortAdmin.signature_img. Same
 * naming convention/pattern as employee_transfers_approval /
 * employee_promotions_approval.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people_salary_increment_status', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('action_date');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });

        // Mirrors employee_transfers.letter_dispatched — tracks whether the
        // increment letter has been emailed to the employee at least once.
        Schema::table('people_salary_increment', function (Blueprint $table) {
            $table->enum('letter_dispatched', ['Yes', 'No'])->default('No')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('people_salary_increment_status', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });
        Schema::table('people_salary_increment', function (Blueprint $table) {
            $table->dropColumn('letter_dispatched');
        });
    }
};
