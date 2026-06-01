<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `completed_via` to exit_clearance_form_assignments so HR can see
 * whether a clearance form was marked Completed from the web UI (HR /
 * HOD) or from the employee's mobile app submission.
 *
 * Values:
 *   • 'web'    → HR or HOD marked complete via the browser
 *   • 'mobile' → employee submitted via the API (ResignationController)
 *   • NULL     → historical row completed before this column existed,
 *                or a row that has not yet reached Completed status.
 *
 * The view surfaces this next to the "Completion Status" badge so HR
 * can tell at a glance which channel closed the form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exit_clearance_form_assignments', function (Blueprint $table) {
            $table->string('completed_via', 10)
                ->nullable()
                ->after('status')
                ->comment("Channel that flipped status to Completed: 'web' or 'mobile'. NULL for in-progress or pre-existing rows.");
        });
    }

    public function down(): void
    {
        Schema::table('exit_clearance_form_assignments', function (Blueprint $table) {
            $table->dropColumn('completed_via');
        });
    }
};
