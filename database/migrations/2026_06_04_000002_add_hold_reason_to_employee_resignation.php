<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `hold_reason` to employee_resignation so the show page can
 * surface why HOD/HR put the resignation on hold. Mirrors the existing
 * `rejected_reason` column — separate field per terminal state so we
 * can render the right banner without status-decoding gymnastics.
 *
 * Idempotent: skips when the column already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_resignation')) {
            return;
        }
        if (Schema::hasColumn('employee_resignation', 'hold_reason')) {
            return;
        }
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->string('hold_reason', 191)->nullable()->after('rejected_reason');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_resignation') || !Schema::hasColumn('employee_resignation', 'hold_reason')) {
            return;
        }
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->dropColumn('hold_reason');
        });
    }
};
