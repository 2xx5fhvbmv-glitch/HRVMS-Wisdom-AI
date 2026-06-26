<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * An old fulfillment bug marked a whole PaymentRequest 'Approved' (Paid) as soon
 * as ONE child/employee in it was settled, even if other employees were still
 * pending. Re-derive: any 'Approved' request that still has a non-Complete child
 * is set back to 'Pending'. The code now only approves when every child is
 * Complete, so this won't recur.
 */
return new class extends Migration
{
    public function up(): void
    {
        $wrong = DB::table('payment_requests as pr')
            ->where('pr.Status', 'Approved')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('payment_request_children as c')
                  ->whereColumn('c.Requested_Id', 'pr.id')
                  ->where(function ($qq) {
                      $qq->whereNull('c.ChildStatus')->orWhere('c.ChildStatus', '!=', 'Complete');
                  });
            })
            ->pluck('pr.id');

        if ($wrong->isNotEmpty()) {
            DB::table('payment_requests')->whereIn('id', $wrong->all())->update(['Status' => 'Pending']);
            Log::info('Payment request fix: reverted ' . $wrong->count() . ' prematurely-approved requests to Pending: ' . $wrong->implode(','));
        }
    }

    public function down(): void
    {
        // Data correction — not reversible.
    }
};
