<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkPermit;
use App\Models\EmployeeInsurance;
use App\Models\QuotaSlotRenewal;
use App\Models\WorkPermitMedicalRenewal;

/**
 * Deletes the local test data created by DummyVisaRenewalRecordsSeeder.
 *
 * Each seeded row is tagged with a 'DUMMY_SEED' marker in a reference/receipt/
 * policy field, so this removes exactly those rows and nothing real.
 *
 *   php artisan visa:remove-dummy-renewals
 */
class RemoveDummyVisaRenewals extends Command
{
    protected $signature = 'visa:remove-dummy-renewals';
    protected $description = 'Delete the seeded dummy visa/work-permit/insurance/quota/medical renewal rows (tagged DUMMY_SEED).';

    const MARKER = 'DUMMY_SEED';

    public function handle()
    {
        $wp   = WorkPermit::where('ReceiptNumber', self::MARKER)->delete();
        $ins  = EmployeeInsurance::where('insurance_policy_number', self::MARKER)->delete();
        $slot = QuotaSlotRenewal::where('ReceiptNumber', self::MARKER)->delete();
        $med  = WorkPermitMedicalRenewal::where('Reference_Number', self::MARKER)->delete();

        $this->info("Removed dummy renewal rows — work permit: {$wp}, insurance: {$ins}, quota slot: {$slot}, medical: {$med}.");

        return Command::SUCCESS;
    }
}
