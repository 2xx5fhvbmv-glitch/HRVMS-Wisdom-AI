<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Modeled after maintanace_requests (HR-raised request against a
        // catalog item, tracked to completion) — NOT the pre-existing
        // housekeeping_schedules/child_housekeeping_schedules tables, which
        // are an unrelated cleaning-schedule/assignment system tied to
        // available_accommodation_models rather than Benefit Grid eligibility.
        Schema::create('housekeeping_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resort_id');
            $table->string('request_id')->unique();
            // batch_id groups the rows created from one HR submission (a
            // single request commonly asks for several services at once)
            // so mobile can render them as one card instead of N separate ones.
            $table->string('batch_id')->index();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('housekeeping_service_id');
            $table->unsignedBigInteger('raised_by');
            $table->string('BuildingName')->nullable();
            $table->string('FloorNo')->nullable();
            $table->string('RoomNo')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'In-Progress', 'Completed'])->default('Pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['resort_id', 'employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('housekeeping_requests');
    }
};
