<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flight-ticket tracking (founder decision 2026-10-29): one row per employee per Annual Leave (anniversary) cycle,
 * recording whether the company has provided that cycle's round-trip ticket. HR can change the answer within the cycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_annual_ticket_records')) {
            Schema::create('employee_annual_ticket_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('resort_id')->index();
                $table->unsignedBigInteger('employee_id');
                $table->date('cycle_start_date');
                $table->date('cycle_end_date');
                $table->boolean('company_provided');
                $table->unsignedBigInteger('decided_by')->nullable();   // resort_admins.id of the HR user
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
            });
        }
        // Named explicitly: the generated name is 66 characters and MySQL allows 64. Added separately so a table left
        // without it by the first (failed) run gets it too.
        if (!collect(Schema::getIndexes('employee_annual_ticket_records'))->contains('name', 'emp_ticket_cycle_unique')) {
            Schema::table('employee_annual_ticket_records', fn (Blueprint $table) => $table->unique(['employee_id', 'cycle_start_date'], 'emp_ticket_cycle_unique'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_annual_ticket_records');
    }
};
