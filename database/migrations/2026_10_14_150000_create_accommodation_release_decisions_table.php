<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR's Yes (vacate) / Keep decision on an Inactive employee's bed / room /
 * villa (founder, 2026-10-05). One open row (pending|keep) per employee;
 * Keep is re-asked every 3 days by accommodation:release-follow-up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_release_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->enum('decision', ['pending', 'vacate', 'keep', 'closed'])->default('pending');
            $table->date('next_check_on')->nullable();
            $table->unsignedInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();
            $table->index(['resort_id', 'decision']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_release_decisions');
    }
};
