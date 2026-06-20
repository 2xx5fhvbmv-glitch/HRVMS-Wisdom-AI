<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks whether an employee's visa/renewal details have been verified &
 * submitted on the verify-details screen. Once 'verified', the employee drops
 * off the verify list (their data now lives on the employee-details page).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_verification_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->enum('status', ['pending', 'verified'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('verified_by')->nullable();
            $table->timestamps();

            $table->unique(['resort_id', 'employee_id']);
            $table->index(['resort_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_verification_statuses');
    }
};
