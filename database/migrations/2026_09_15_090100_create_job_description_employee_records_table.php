<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobDescriptionEmployeeRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('job_description_employee_records', function (Blueprint $table) {
            $table->id();
            // resorts.id / employees.id are increments() (INT UNSIGNED), not
            // bigIncrements — must match or the FK constraint fails.
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('job_description_id');
            $table->unsignedInteger('employee_id');

            // Employer/employee identity + signatures are snapshotted at
            // generation time (same reasoning as Common::snapshotSignature()) —
            // a JD downloaded years later must show what was true when issued,
            // not today's live employee/resort data.
            $table->string('employer_name');
            $table->string('employer_address')->nullable();
            $table->string('employer_nationality')->nullable();
            $table->string('employer_type_of_work')->nullable();

            $table->string('employee_full_name');
            $table->text('employee_permanent_address')->nullable();
            $table->text('employee_current_address')->nullable();
            $table->string('employee_id_card_number')->nullable();
            $table->date('employee_dob')->nullable();
            $table->string('employee_nationality')->nullable();

            $table->string('employer_signature_path')->nullable();
            $table->string('employee_signature_path')->nullable();

            $table->enum('status', ['Pending', 'Signed', 'Declined'])->default('Pending');
            $table->text('decline_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->unsignedInteger('resend_count')->default(0);

            $table->string('pdf_path')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->timestamps();

            // Explicit short name — the auto-generated one exceeds MySQL's 64-char identifier limit.
            $table->unique(['job_description_id', 'employee_id'], 'jd_employee_records_unique');
            $table->foreign('job_description_id')->references('id')->on('job_descriptions')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('resort_id')->references('id')->on('resorts');
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_description_employee_records');
    }
}
