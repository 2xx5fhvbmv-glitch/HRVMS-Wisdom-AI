<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Test data for the app developer to verify the "Received Consent Requests"
 * fix on GET shop/employee-dashboard (Payment::with(['shopKeeper','product'])
 * where status='Pending Consent') — resort 26 had no Pending Consent rows to
 * actually exercise the fixed list, only the aggregate count.
 *
 * Anastasia Volkova (employee id 173, Emp_id DR-4, resort 26) — Tuck Shop
 * (shopkeeper_id 49), 2x Cigarette (product_id 46) @ 100.00 = 200.00, matching
 * the "$200" pending-consent figure from the bug report.
 */
class SeedTestPendingConsentPayment extends Migration
{
    public function up()
    {
        DB::table('payments')->insert([
            'shopkeeper_id' => 49,
            'order_id'      => 'TEST-CONSENT-' . uniqid(),
            'emp_id'        => 173,
            'purchased_date' => now()->format('Y-m-d'),
            'product_id'    => 46,
            'quantity'      => 2,
            'price'         => 100.00,
            'status'        => 'Pending Consent',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down()
    {
        DB::table('payments')
            ->where('emp_id', 173)
            ->where('order_id', 'like', 'TEST-CONSENT-%')
            ->delete();
    }
}
