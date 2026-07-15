<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddConsentRejectedToApplicantAvailabilityStatus extends Migration
{
    /**
     * Consent rejection (ConsentResponseController::reject()) never touched
     * availability_status, so a rejected applicant kept showing whatever
     * stale value ("Available to Reach") was there before — misleading,
     * since retaining/processing their data after they withdrew consent is
     * a compliance concern, not just a display nitpick. Reusing the
     * existing 'unavailable' value would conflate "temporarily can't
     * reach them" with "they withdrew consent", so this adds a distinct
     * enum value instead.
     */
    public function up()
    {
        DB::statement("ALTER TABLE applicant_form_data MODIFY COLUMN availability_status ENUM('pending', 'available', 'unavailable', 'consent_rejected')");
    }

    public function down()
    {
        DB::statement("ALTER TABLE applicant_form_data MODIFY COLUMN availability_status ENUM('pending', 'available', 'unavailable')");
    }
}
