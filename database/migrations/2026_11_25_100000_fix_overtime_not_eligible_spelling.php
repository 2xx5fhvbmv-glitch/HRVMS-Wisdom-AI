<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** The compliance rule was stored as "Over Time Not Eligibile"; the code now writes "Over Time Not Eligible". */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('compliances')->where('compliance_breached_name', 'Over Time Not Eligibile')
            ->update(['compliance_breached_name' => 'Over Time Not Eligible']);
    }

    public function down(): void
    {
        DB::table('compliances')->where('compliance_breached_name', 'Over Time Not Eligible')
            ->update(['compliance_breached_name' => 'Over Time Not Eligibile']);
    }
};
