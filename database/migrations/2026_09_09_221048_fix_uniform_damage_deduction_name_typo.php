<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// resort_deductions.id=6 (resort 26) was seeded/entered as "Uniform Damgae"
// — a typo of "Uniform Damage" that surfaces on the Review Final Settlement
// page's Deductions table wherever this deduction type is used.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('resort_deductions')
            ->where('deduction_name', 'Uniform Damgae')
            ->update(['deduction_name' => 'Uniform Damage']);
    }

    public function down(): void
    {
        DB::table('resort_deductions')
            ->where('deduction_name', 'Uniform Damage')
            ->update(['deduction_name' => 'Uniform Damgae']);
    }
};
