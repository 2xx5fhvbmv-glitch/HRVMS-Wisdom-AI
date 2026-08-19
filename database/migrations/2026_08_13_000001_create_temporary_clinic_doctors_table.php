<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A third-party/agency doctor isn't an Employee — they're not on payroll,
 * shouldn't appear in headcount/benefit/report queries, and only ever need
 * mobile-app access to the Clinic module. Mirrors the `shopkeepers` table's
 * standalone-Authenticatable pattern rather than reusing Employee+rank=12
 * (which is what a real in-house clinic staff login still uses).
 *
 * The 4 capability booleans are the only Clinic Manager actions HR can grant
 * — appointment-category management and leave-approval actions are
 * deliberately not exposed to this account type at all (see ClinicController
 * routes/api.php comments).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('temporary_clinic_doctors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('contact_no')->nullable();
            $table->string('agency_name')->nullable();
            $table->boolean('can_view_appointments')->default(false);
            $table->boolean('can_manage_treatment')->default(false);
            $table->boolean('can_view_medical_history')->default(false);
            $table->boolean('can_issue_medical_certificate')->default(false);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('temporary_clinic_doctors');
    }
};
