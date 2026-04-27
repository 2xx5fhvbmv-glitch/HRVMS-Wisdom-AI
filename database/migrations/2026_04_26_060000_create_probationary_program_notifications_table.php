<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which (employee × probationary-program × kind) reminders we've already
 * sent, so the dashboard-driven reminder flow stays idempotent — no duplicates
 * however many times the user opens the dashboard.
 *
 * Kinds: 'pending' (one-off "remember to complete this") and 'overdue'
 * (sent once when the program flips past its due date, with a copy to the
 * employee's reporting manager).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('probationary_program_notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id');
            $t->unsignedBigInteger('program_id');
            $t->string('kind', 20); // 'pending' | 'overdue'
            $t->timestamp('sent_at')->useCurrent();
            $t->timestamps();
            $t->unique(['employee_id', 'program_id', 'kind'], 'probationary_notif_unique');
            $t->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('probationary_program_notifications');
    }
};
