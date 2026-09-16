<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * t_anotification_children.Approved_By stores a RANK CODE (3/7/8), not an
 * individual person's id — there's no existing column recording WHICH
 * specific person acted at a stage. signature_name (frozen via
 * Common::snapshotSignature() at the moment of approval) becomes that
 * record, same naming convention as employee_transfers_approval /
 * employee_promotions_approval / people_salary_increment_status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_anotification_children', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('holding_date');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('t_anotification_children', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });
    }
};
