<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The run computes and pays Friday OT (×1.5), and PayrollController::store
// already writes 'fridayOTPay' — but the column never existed, so the value
// was silently dropped and every view had to derive it as total − regular −
// holiday. Add it, filled for past reviews with that same derivation.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payroll_reviews', 'fridayOTPay')) {
            Schema::table('payroll_reviews', function (Blueprint $table) {
                $table->decimal('fridayOTPay', 12, 2)->default(0)->after('regularOTPay');
            });
        }
        DB::table('payroll_reviews')->update([
            'fridayOTPay' => DB::raw('GREATEST(0, COALESCE(earnings_overtime,0) - COALESCE(regularOTPay,0) - COALESCE(holidayOTPay,0))'),
        ]);
    }

    public function down(): void
    {
        Schema::table('payroll_reviews', fn (Blueprint $table) => $table->dropColumn('fridayOTPay'));
    }
};
