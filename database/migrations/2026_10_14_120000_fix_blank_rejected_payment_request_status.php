<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// PaymentRequestRejected() wrote 'Reject', which isn't in the Status enum
// ('Rejact' is), so MariaDB (non-strict) stored ''. The reject path is the
// only writer that ever produced a value outside the enum, so every blank
// row is a rejected request.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_requests')->where('Status', '')->update(['Status' => 'Rejact']);
    }

    public function down(): void
    {
        // Not reversible: '' was never a valid value.
    }
};
