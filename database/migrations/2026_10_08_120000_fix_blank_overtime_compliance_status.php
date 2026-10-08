<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The overtime-eligibility rule wrote status 'Compliant', which isn't in the
// enum, so MariaDB (non-strict) stored ''. Those rows are open breaches.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('compliances')
            ->where('compliance_breached_name', 'Over Time Not Eligibile')
            ->where('status', '')
            ->update(['status' => 'Breached']);
    }

    public function down(): void
    {
        // Not reversible: '' was never a valid value.
    }
};
