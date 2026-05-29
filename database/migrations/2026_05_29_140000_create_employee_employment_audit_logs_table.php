<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log for changes made to the Employment tab on the Employee
 * Detail page. Every edited field becomes one row so HR can trace who
 * changed what, when. Surfaced at the bottom of the Employment tab
 * with pagination (people.employees.employment-logs endpoint).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('employee_employment_audit_logs')) {
            return;
        }

        Schema::create('employee_employment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resort_id');
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('field');                 // e.g. 'personal_phone'
            $table->string('label')->nullable();     // human-readable label, e.g. 'Mobile Number'
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable(); // resort_admins.id
            $table->timestamps();

            $table->index(['employee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employment_audit_logs');
    }
};
